<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\MessageType;
use AppBundle\Form\UserType;
use AppBundle\Repository\MessageRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;     // Аннотации
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;  // Аннотации
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class DefaultController extends Controller
{
    /**
     * Страница регистрации/авторизации.
     *
     * @Route("/", name="homepage")
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        /** @var User $userExists */
        $userExists = $this->getDoctrine()->getRepository('AppBundle:User')
            ->findOneBy(['username' => $form->getData()->getUsername()]);

        if($userExists) {
            $this->authenticateUser($userExists);
            return $this->redirectToRoute('app');
        }

        if($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->authenticateUser($user);
            return $this->redirectToRoute('app');
        }

        return $this->render('AppBundle:default:index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Страница приложения.
     *
     * @Route("/app", name="app")
     * @Security("has_role('ROLE_USER')")
     * @return Response
     */
    public function appAction()
    {
        /** @var MessageRepository $repo */
        $repo = $this->getDoctrine()->getRepository('AppBundle:Message');
        $messages = $repo->limit(20)->getAll();

        $form = $this->createForm(MessageType::class);
        
        return $this->render('AppBundle:default:app.html.twig', [
            'messages' => $messages,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Загрузка файлов.
     *
     * @Route("/app/file-upload")
     * @Security("has_role('ROLE_USER')")
     * @param Request $request
     * @return Response
     */
    public function uploadFileAction(Request $request)
    {
        if(!$request->getMethod() == Request::METHOD_POST) {
            throw $this->createNotFoundException();
        }

        /** @var UploadedFile $file */
        foreach ($request->files as $file) {
            $filename = md5(uniqid() . time())  .'.'. $file->getClientOriginalExtension();
            $file->move($this->getParameter('upload_ab_dir'), $filename);

            return new Response($this->getParameter('site_url') . $this->getParameter('upload_dir') . $filename);
        }
    }

    /**
     * Метод авторизует юзера.
     *
     * @param User $user
     * @return void
     */
    protected function authenticateUser(User $user)
    {
        $token = new UsernamePasswordToken($user, null, 'secured_area', $user->getRoles());
        $this->get('security.context')->setToken($token);
        $this->get('session')->set('_security_secured_area', serialize($token));
    }
}
