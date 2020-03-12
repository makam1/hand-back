<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Enfant;
use App\Entity\Groupe;
use App\Entity\Evenement;
use App\Form\UserType;
use App\Form\LoginType;
use App\Form\EnfantType;
use App\Form\EvenementType;
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

        $utilisateur = new User();

        $form = $this->createForm(LoginType::class, $utilisateur);
        $form->handleRequest($request);
        $data=$request->request->all();

        $form->submit($data);
        
        $form->handleRequest($request);

        $user=$this->getDoctrine()->getRepository(User::class)->findOneBy(array('username'=>$utilisateur->getUsername()));

        if($user==null ){
            return new JsonResponse("Nom d'utilisateur ou mot de passe erroné réessayer",500, [
                'Content-Type'=>  'application/json'
            ]);
                }else{
            $pass=$this->passwordEncoder->isPasswordValid($user,$utilisateur->getPassword());
            if($pass==false){
                return new JsonResponse("Nom d'utilisateur ou mot de passe erroné réessayer",500, [
                    'Content-Type'=>  'application/json'
                ]);
            }
            $token = $JWTEncoder->encode([
                'roles'=>$user->getRoles(),
                'username' => $user->getUsername(),  
            ]);
        return new JsonResponse(['token' =>$token]);
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
        $utilisateur->setRoles([$utilisateur->getRole()]);
        $entityManager->persist($utilisateur);
        $entityManager->flush(); 
       
        return new JsonResponse('Compte crée, Bienvenue'.$utilisateur->getUsername(),200, [
            'Content-Type' => 'application/json'
        ]);
    }
    /**
     * @Route("/ajout", name="ajout", methods={"GET","POST"})
     * 
     */
    public function ajout(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $encoder,SerializerInterface $serializer,ValidatorInterface $validator): Response
    {

        $g=$this->getUser()->getGroupe();
        $utilisateur = new User();
        $form = $this->createForm(UserType::class, $utilisateur);
        $form->handleRequest($request);
        $data=$request->request->all();
        $file=$request->files->all()['imageFile'];
        $form->submit($data);
        $hash = $encoder->encodePassword($utilisateur, $utilisateur->getPassword());
        $utilisateur->setPassword($hash);
        $utilisateur->setGroupe($g);
        $utilisateur->setRoles([$utilisateur->getRole()]);
        $utilisateur->setImageFile($file);
        $entityManager->persist($utilisateur);
        if($utilisateur->getRole()=='ROLE_ENFANT'){
            $enfant = new Enfant();
            $form1 = $this->createForm(EnfantType::class, $enfant);
            $form1->handleRequest($request);
            $form1->submit($data);
            $enfant->setUser($utilisateur);
            if($enfant->getEtablissement()==null|| $enfant->getNiveauscolaire()==null){

                $enfant->setNiveauscolaire('non mentionné');
                $enfant->setEtablissement('non mentionné');
            }
        
            $entityManager->persist($enfant);


        }
        $entityManager->flush();
            
        return new JsonResponse('membre ajouté avec succés',200, [
            'Content-Type' =>  'application/json'
        ]);
    }
   
    /**
     * @Route("/evenement", name="evenement", methods={"GET","POST"})
     * 
     */
    public function evenement(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $encoder,SerializerInterface $serializer,ValidatorInterface $validator): Response
    {

        $g=$this->getUser()->getGroupe();
        $event = new Evenement();
        $form = $this->createForm(EvenementType::class, $event);
        $form->handleRequest($request);
        $data=$request->request->all();
        $form->submit($data);
        if($event->getDescriptif()==null){
            $event->setDecriptif($event->getLibelle());   
        }
        if($event->getDatefin()==null){
            $event->setDatefin($event->getDatedebut());   
        }
        if($event->getheuredebut()==null){
            $event->setheuredebut('00:00');   
        }else{
            if($event->getheurefin()==null){
                $event->setheurefin($event->getheuredebut());   
            }
        }
        if($event->getheurefin()==null){
            $event->setheurefin('00:00');   
        }
        
        $event->setGroupe($g);   

            $entityManager->persist($event);
        $entityManager->flush();
            
        return new JsonResponse('evenement ajouté avec succés',200, [
            'Content-Type' =>  'application/json'
        ]);
    }
   

}
    