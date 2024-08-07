<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Detail;
use App\Entity\Utilisateur;
use App\Repository\PlatRepository;
use App\Repository\DetailRepository;
use App\Repository\CommandeRepository;
use App\Repository\UtilisateurRepository;
use App\Form\ContactFormType;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Request;



#[Route('/commande', name: 'app_commande_')]
class CommandeController extends AbstractController
{






    #[Route('/ajout', name: 'add')]
    public function add(SessionInterface $session, PlatRepository $platRepository, EntityManagerInterface $em, UtilisateurRepository $utilisateurRepository, MailService $emailService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $panier = $session->get('panier', []);

        if ($panier === []) {

            $this->addFlash('message', 'Votre panier est vide');
            return $this->redirectToRoute('app_accueil');
        }

        // dd($panier);

        // Le panier n'est pas vide, on crée la commande
        $commande = new commande();


        // On remplit la commande
        $commande->setUtilisateur($this->getUSER());
        $commande->setDateCommande(new \DateTime());
        $commande->setEtat(0);
        $commande->setTotal(0);

        $prix = 0;
        //dd($panier);

        // On parcourt le panier pour créer les détails de commande

        foreach ($panier as $item => $quantite) {
            $detail = new detail();

            // On va chercher le plat

            $plat = $platRepository->find($item);
            $prix = $prix + ($quantite * $plat->getprix());

            // On crée le détail de commande

            $detail->setplat($plat);

            $detail->setquantite($quantite);

            $commande->adddetail($detail);
        }

        $commande->setTotal($prix);

        // On persiste et on flush

        $em->persist($commande);

        $em->flush();

        // $utilisateur=new Utilisateur();
         $idUser = $this->getUSER();



        // recuperer l'utilisateur avec son id
        // Pour cela tu dois créer une methode dans le repository de utilisateur que va recuperer l'utilisateur par son id
         $utilisateur = $utilisateurRepository->findById($idUser);

         $emailService->sendCommande('info@thedistrict.com', $utilisateur->getEmail(), $commande);


        // if($em->flush()){

               $session->remove('panier');

               $this->addFlash('message', 'Commande créée avec succès');
        //     return $this->redirectToRoute('app_panier');
        // }


        return $this->render('commande/index.html.twig', [
            'controller_name' => 'CommandeController',
        ]);
    }
}
