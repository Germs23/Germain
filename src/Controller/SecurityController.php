<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/inscription", name="security_registration")
     */
    public function registration(Request $request, EntityManagerInterface $manager,UserPasswordHasherInterface $encoder){
        $user = new User();

        $form = $this->createFormBuilder($user)
                    ->add('username',TextType::class,[
                        'attr' => [
                            'placeholder' => "Name",
                        ],
                        'label' => 'User.name',
                    ])
                    ->add('email',TextType::class, [
                        'attr' => [
                            'placeholder' => "Email"
                        ],
                        'label' => 'User.email',
                    ])
                    ->add('password',PasswordType::class,[
                        'attr' => [
                            'placeholder' => "Password"
                        ],
                        'label' => 'User.password',
                    ])
                    ->add('confirm_password',PasswordType::class,[
                        'attr' => [
                            'placeholder' => 'Confirm password'
                        ],
                        'label' => 'User.confirm.password',
                    ])
                    ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $role = ['ROLE_USER'];

            $hash = $encoder->hashPassword($user,$user->getPassword());

            $user->setPassword($hash);
            $user->setRoles($role);

            $manager->persist($user);
            $manager->flush();

            return $this->redirectToRoute('security_login');
        }

        return $this->render('security/registration.html.twig',[
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/connexion", name="security_login")
     */
    public function login(){
        return $this->render('security/login.html.twig');
    }

    /**
     * @Route("/deconnexion", name="security_logout")
     */
    public function logout(){

    }

    /**
     * @Route("/denyAccess", name="security_redirection")
     */
    public function redirectAccessDeny(){
        return $this->render('security/redirection.html.twig');
    }
}
