<?php

namespace App\Controller;

use DateTime;
use App\Entity\Vehicule;
use App\Form\VehiculeType;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VehiculeController extends AbstractController

// ========= Route pour Afficher TOUS LES VEHICULES ======
{
#[Route("admin/vehicules", name:"admin_app_vehicules")]
#[Route("liste_vehicules", name:"liste_vehicules")]

    public function adminVehicules(ManagerRegistry $doctrine): Response
    {
        $vehicules = $doctrine->getRepository(Vehicule::class)->findAll();

        return $this->render('vehicule/admin/adminVehicules.html.twig', [
            'vehicules' => $vehicules
        ]);
    }

    // ========= Route pour AJOUTER un NEW VEHICULE ======

#[Route("/admin/vehicule-ajout", name:"admin_ajout_vehicule")]

    public function ajout(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {
        // SECURITE ---> Si l'User n'est PAS Authentifié |-> !$this |-> Voir dans Layout
        if( !$this->isGranted('IS_AUTHENTICATED_FULLY') ){
            $this->addFlash('error', "Veuillez vous connecter pour accéder à ces pages" );
        return $this->redirectToRoute('app_login');
        }
        // SECURITE ---> Si l'User n'est PAS Authentifié comme ADMIN |-> !$this |-> Voir dans Layout
        if( !$this->isGranted('ROLE_ADMIN') ){
            $this->addFlash('error', "Veuillez vous connecter comme Administrateur pour accéder à ces pages" );
        return $this->redirectToRoute('app_home');
        }

        $vehicule = new Vehicule();

        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {

            // on recupere l'image depuis le formulaire
            $file = $form->get('imageForm')->getData();
            //dd($file);
            //dd($vehicule);
            // le slug permet de modifier une chaine de caractéres : mot clé => mot-cle
            $fileName = $slugger->slug( $vehicule->getTitre() ) . uniqid() . '.' . $file->guessExtension();

            try{
                // on deplace le fichier image recuperé depuis le formulaire 
                // dans le dossier parametré dans la partie Parameters du fichier config/service.yaml, 
                // avec pour nom $fileName
                
            $file->move($this->getParameter('photos_vehicules'),  $fileName);
            }catch(FileExeption $e)
            {
                // gérer les exeptions en cas d'erreur durant l'upload
            }

            $vehicule->setPhoto($fileName);

            $vehicule->setDateEnregistrement(new DateTime("now"));

            $manager = $doctrine->getManager();
            $manager->persist($vehicule);
            $manager->flush();
             //message dans layout tjs avant redirectToRoute
            $this->addFlash('success', "Votre véhicule à bien été ajouté");
            return $this->redirectToRoute("app_home");
        }
        
        return $this->render("vehicule/admin/formulaire.html.twig", [
            "formVehicule" => $form->createView()
        ]);

    }

        // ========= Route pour MODIF un VEHICULE avec IMAGE ======

#[Route("/admin/update_vehicule/{id<\d+>}", name:"admin_update_vehicule")] 

    public function update(ManagerRegistry $doctrine, $id, Request $request, SluggerInterface $slugger): Response
    {
        // SECURITE ---> Si l'User n'est PAS Authentifié |-> !$this |-> Voir dans Layout
        if( !$this->isGranted('IS_AUTHENTICATED_FULLY') ){
            $this->addFlash('error', "Veuillez vous connecter pour accéder à ces pages" );
        return $this->redirectToRoute('app_login');
        }
        // SECURITE ---> Si l'User n'est PAS Authentifié comme ADMIN |-> !$this |-> Voir dans Layout
        if( !$this->isGranted('ROLE_ADMIN') ){
            $this->addFlash('error', "Veuillez vous connecter comme Administrateur pour accéder à ces pages" );
        return $this->redirectToRoute('app_home');
        }

        $vehicule = $doctrine->getRepository(Vehicule::class)->find($id);

        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        // on stock l'image du vehicule à mettre à jour
        $image = $vehicule->getPhoto();

        if($form->isSubmitted() && $form->isValid())
        {
            // si une image a bien été ajouté au formulaire
            if($form->get('imageForm')->getData() )
            {
                // on recupere l'image du formulaire
                $imageFile = $form->get('imageForm')->getData();
    
                //on crée un nouveau nom pour l'image
                $fileName = $slugger->slug($vehicule->getTitre()) . uniqid() . '.' . $imageFile->guessExtension();
    
                //on deplace l'image dans le dossier parametré dans service.yaml
                try{
                    $imageFile->move($this->getParameter('photos_vehicules'), $fileName);
                }catch(FileException $e){
                    // gestion des erreur upload
                }
                $vehicule->setPhoto($fileName);
                
            }
                $manager= $doctrine->getManager();
                $manager->persist($vehicule);
                $manager->flush();
                //message dans layout tjs avant redirectToRoute
                $this->addFlash('success', "Votre véhicule à bien été mis à jour");
                return $this->redirectToRoute('admin_app_vehicules');
        }

        return $this->render("vehicule/admin/formulaire.html.twig", [
            'formVehicule' => $form->createView()
        ]);
    }

        // ========= Route pour DELETE un VEHICULE ======

    #[Route("/admin/delete/{id<\d+>}", name:"admin_delete_vehicule")]
    public function delete($id, ManagerRegistry $doctrine, VehiculeRepository $repo): Response
    {
        // SECURITE ---> Si l'User n'est PAS Authentifié |-> !$this |-> Voir dans Layout
        if( !$this->isGranted('IS_AUTHENTICATED_FULLY') ){
            $this->addFlash('error', "Veuillez vous connecter pour accéder à ces pages" );
        return $this->redirectToRoute('app_login');
        }
        // SECURITE ---> Si l'User n'est PAS Authentifié comme ADMIN |-> !$this |-> Voir dans Layout
        if( !$this->isGranted('ROLE_ADMIN') ){
            $this->addFlash('error', "Veuillez vous connecter comme Administrateur pour accéder à ces pages" );
        return $this->redirectToRoute('app_home');
        }
        
        $vehicule = $repo->find($id);
        // 1 -> fait passer le flush a true dans VehiculeRepository
        $repo->remove($vehicule, 1);
        
        //message dans layout tjs avant redirectToRoute
        $this->addFlash('success', "Votre véhicule à bien été effacé");
        return $this->redirectToRoute('admin_app_vehicules');
    }

}
