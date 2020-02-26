<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Enfant;
use App\Entity\Groupe;
use App\Form\UserType;
use App\Form\LoginType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


/**
 * @Route("/api")
 */
class UserController extends AbstractController
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
    $this->passwordEncoder = $passwordEncoder;
    }
        /**
     *@Route("/login", name="connexion", methods={"POST"})
     * @return JsonResponse
     * @param Request $request
     */

    public function login(Request $request, JWTEncoderInterface $JWTEncoder,AuthenticationUtils $authenticationUtils)
    
    {

        // $user = $this->getUser();
        // var_dump($user);die();

        // $isValid =$this->passwordEncoder;
        // if (!$isValid) {
        //     return new JsonResponse('Votre username ou votre mot de passe est incorrect, veuillez saisir à nouveau');
        // }
        $utilisateur = new User();

        $form = $this->createForm(LoginType::class, $utilisateur);
        $form->handleRequest($request);
        $data=$request->request->all();

        $form->submit($data);
        
        $form->handleRequest($request);

        $user=$this->getDoctrine()->getRepository(User::class)->findOneBy(array('username'=>$utilisateur->getUsername()));

        if($user==null ){
            return new JsonResponse(['erreur' => "Nom d'utilisateur ou mot de passe erroné réessayer"]);
        }else{
            $pass=$this->passwordEncoder->isPasswordValid($user,$utilisateur->getPassword());
            if($pass==false){
                return new JsonResponse(['erreur' => "Nom d'utilisateur ou mot de passe erroné réessayer"]);
            }
            $token = $JWTEncoder->encode([
                'roles'=>$user->getRoles(),
                'username' => $user->getUsername(),
            ]);
        return new JsonResponse(['token' => $token]);
        }
        
    }
    /**
     * @Route("/login/inscription", name="inscription", methods={"GET","POST"})
     * 
     */
    public function inscription(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $encoder,SerializerInterface $serializer,ValidatorInterface $validator): Response
    {

        $utilisateur = new User();
        $groupe= new Groupe();

        $form = $this->createForm(UserType::class, $utilisateur);
        $form->handleRequest($request);
        $data=$request->request->all();

        $form->submit($data);

        $hash = $encoder->encodePassword($utilisateur, $utilisateur->getPassword());
        $utilisateur->setPassword($hash);
        $groupe->setNomgroupe($utilisateur->getEmail());
        $entityManager->persist($groupe);
        $utilisateur->setGroupe($groupe);
        $utilisateur->setRoles(["ROLE_PARENT"]);
        $entityManager->persist($utilisateur);
        $entityManager->flush(); 
       
        return new JsonResponse('Compte crée, Bienvenue'.$utilisateur->getUsername(),200, [
            'Content-Type'=>  'application/json'
        ]);
    }
    /**
     * @Route("/parent", name="parent", methods={"GET","POST"})
     * 
     */
    public function parent(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $encoder,SerializerInterface $serializer,ValidatorInterface $validator): Response
    {

        $g=$this->getUser()->getGroupe();
        $utilisateur = new User();
        $form = $this->createForm(UserType::class, $utilisateur);
        $form->handleRequest($request);
        $data=$request->request->all();
        $form->submit($data);
        $hash = $encoder->encodePassword($utilisateur, $utilisateur->getPassword());
        $utilisateur->setPassword($hash);
        $utilisateur->setGroupe($g);
    

        $entityManager->persist($utilisateur);
        $entityManager->flush();
            
        return new JsonResponse('membre ajouté avec succés',200, [
            'Content-Type'=>  'application/json'
        ]);
    }
   

}
    