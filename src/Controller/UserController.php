<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\UserPassportInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/", name="app_user")
     */
    public function index(): Response
    {
        return $this->render('user/index.html.twig');
    }
    /**
     * @Route("/inscription", name="registration")
     */
    public function inscription(ManagerRegistry $doctrine, Request $resq,
    UserPasswordEncoderInterface $encoder, SluggerInterface $sluger){
        $user = new User();
        $from = $this->createForm(UserType::class, $user);

        $from->handleRequest($resq);
        if($from->isSubmitted() && $from->isValid()){
            $hash = $encoder->encodePassword($user,$user->getPassword());
            $user->setPassword($hash);
            /**
             * @var UploadedFile $imgFile
             */
            $imgFile =$from->get('avatar')->getData();
            if($imgFile){
                $originalFilename = pathinfo($imgFile->getClientOriginalName(),\PATHINFO_FILENAME);
                $safeFileName = $sluger->slug($originalFilename);
                $newFileName = $safeFileName."-".uniqid().".".$imgFile->guessExtension();
            
                try {
                    $imgFile->move($this->getParameter('avatar'),$newFileName);
                    
                } catch (FileException $e){
                    $e->getMessage();
                }
                $user->setAvatar($newFileName);
            }
            $objectManager = $doctrine->getManager();
            $objectManager->persist($user);
            $objectManager->flush();
            return $this->redirectToRoute("app_user");

        }

        return $this ->render("user/add.html.twig",["formulaire" =>$from->createView()
        ]);
    }
}
