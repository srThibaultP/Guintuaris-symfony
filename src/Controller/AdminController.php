<?php

namespace App\Controller;

use App\Entity\Bestiaire;
use App\Entity\Competence;
use App\Entity\Equipe;
use App\Entity\Personnage;
use App\Entity\PieceArmure;
use App\Entity\TypeArmure;
use App\Entity\TypeBestiaire;
use App\Form\BestiaireType;
use App\Form\CompetenceType;
use App\Form\PieceArmureType;
use App\Repository\PersonnageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin", name="admin_")
 */
class AdminController extends AbstractController
{
    /**
     * affiche le lien des pour acceder au fonctionaliter administrateur
     * 
     * @Route("/", name="home")
     * 
     * @return Response
     */
    public function adminHome(): Response
    {
        return $this->render('admin/admin.html.twig', []);
    }


    /**
     * permet d'ajouter une nouvelle competence dans la base de donnée (table competence)
     * 
     * @Route("/add_competence", name="add_competence")
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function competence(Request $request, EntityManagerInterface $entityManager): Response
    {
        $competence = new Competence();
        $competenceForm = $this->createForm(CompetenceType::class, $competence);

        $competenceForm->handleRequest($request);
        if ($competenceForm->isSubmitted()) {

            $entityManager->persist($competence);
            $entityManager->flush();
        }
        return $this->render('admin/addcompetence.html.twig', [
            "competenceForm" => $competenceForm->createView()
        ]);
    }
    /**
     * permet d'ajouter une nouvel Piece d'armure dans la BDD (table Piece_armure)
     * permet aussi d'afficher toute les instance de cette table
     * 
     * @Route("/ajout_piece", name="add_piece")
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $this->getDoctrine()->getRepository(PieceArmure::class);
        $piecesTab = $repo->findAll();
        $piece = new PieceArmure();
        $pieceForm = $this->createForm(PieceArmureType::class, $piece);

        $pieceForm->handleRequest($request);
        if ($pieceForm->isSubmitted()) {
            $entityManager->persist($piece);
            $entityManager->flush();

            return $this->redirectToRoute('add_piece');
        }
        return $this->render('admin/addPiece.html.twig', [
            "pieceForm" => $pieceForm->createView(),
            "piecesTab" => $piecesTab
        ]);
    }
    /**
     * affiche dans un select tout les personnage present dans l'equipe aucune
     * lorsque le formulaire est valider le persnnage selectioner changer d'equipe et a pour equipe celle selectionner dans la page admin_equipe_list
     * de plus la page affiche le nom de tout les personnage apartenent a l'equipe (les nom emmenent ensuite vers leur fiche)
     * 
     * @Route("/ajout_membre/{idEquipe}", name="add_membre")
     *
     * @param int $idEquipe
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function addMembreEquipe(int $idEquipe, Request $request, EntityManagerInterface $entityManager): Response
    {
        $membresEquipe = $this->getDoctrine()->getRepository(Personnage::class)->findBy(['equipe' => $idEquipe]); //list des Memebre apartennant a cette equipe
        $equipeJoin = $this->getDoctrine()->getRepository(Equipe::class)->find($idEquipe); //represente la L'equipe sur la quelle des membre vont etre ajouter
        $membreForm = $this->createFormBuilder()
            ->add('personnage', EntityType::class, [
                'class' => Personnage::class,
                'choice_label' => 'nom',
                'query_builder' => function (PersonnageRepository $pr) {
                    return $pr->createQueryBuilder('p')
                        ->where('p.equipe = 5') //si l'equipe choisi est 5 (aucune), alors on recherche tout les joueurs apparteant a une Equipe 
                        ->orderBy('p.nom', 'ASC');
                }
            ])
            ->getForm();
        $membreForm->handleRequest($request);
        if ($membreForm->isSubmitted()) {
            $personnageSelectionner = $membreForm->get('personnage')->getData();
            $personnageSelectionner->setEquipe($equipeJoin);
            dump($personnageSelectionner);

            $entityManager->persist($personnageSelectionner);
            $entityManager->flush();
            return $this->redirectToRoute('admin_add_membre', ['idEquipe' => $idEquipe]);
        }
        return $this->render('admin/addMembreEquipe.html.twig', [
            'membreForm' => $membreForm->createView(),
            'membresEquipe' => $membresEquipe,
        ]);
    }

    /**
     * affiche le nom de toute les equipe et emment ensuite vers admin_add_membre
     * de plus un formulaire permet de créer une nouvelle equipe
     *
     *  @Route("/equipe", name="equipe_list")
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function listEquipeAdmin(Request $request, EntityManagerInterface $entityManager): Response
    {
        $newEquipe = new Equipe();
        $results = $this->createFormTable($newEquipe, Equipe::class);
        $equipes = $results[0];
        $addEquipeForm = $results[1];
        $addEquipeForm->handleRequest($request);
        if ($addEquipeForm->isSubmitted()) {
            $entityManager->persist($newEquipe);
            $entityManager->flush();
            return $this->redirectToRoute("admin_add_membre", ['idEquipe' => $newEquipe->getId()]);
        }
        return $this->render('admin/listEquipe.html.twig', [
            'equipes' => $equipes,
            'addEquipeForm' => $addEquipeForm->createView()
        ]);
    }

    /**
     * permet d'ajouter une bete dans la base de donnée  (table bestiaire)
     * 
     * @Route("/bestiaire", name="add_bete")
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function addBete(EntityManagerInterface $entityManager, Request $request): Response
    {
        $bete = new Bestiaire();
        $beteForm = $this->createForm(BestiaireType::class, $bete);

        $beteForm->remove('pv');
        $beteForm->remove('pc');
        $beteForm->remove('pm');
        $beteForm->handleRequest($request);
        if ($beteForm->isSubmitted()) {
            $bete->setPv($bete->getPvMax());
            $bete->setPc($bete->getPcMax());
            $bete->setPm($bete->getPmMax());

            dump($bete);
            $entityManager->persist($bete);
            $entityManager->flush();
            return $this->redirectToRoute("admin_add_bete");
        }
        return $this->render('admin/addBete.html.twig', [
            "beteForm" => $beteForm->createView(),
        ]);
    }
    /**
     * permet d'ajouter un nouveau type de bete dans la base de donné (table type_bestiaire)
     * affiche toute les instance se trouvant dans cette table
     * 
     * @Route("/list_type_bestiaire", name="type_bestiaire_list")
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function addTypeBestiaire(Request $request, EntityManagerInterface $entityManager): Response
    {
        $newType = new TypeBestiaire();
        $results = $this->createFormTable($newType, TypeBestiaire::class);
        $typesBestiaire = $results[0];
        $addTypeForm = $results[1];
        $addTypeForm->handleRequest($request);
        if ($addTypeForm->isSubmitted()) {
            $entityManager->persist($newType);
            $entityManager->flush();
        }
        return $this->render('admin/listTable.html.twig', [
            'list' => $typesBestiaire,
            'form' => $addTypeForm->createView()
        ]);
    }

    /**
     * permet d'ajouter un nouveau type d'armure dans la base de donné (table type_armure)
     * affiche toute les instance se trouvant dans cette table
     *
     *  @Route("/list_type_armure", name="type_armure_list")
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function addTypeArmure(Request $request, EntityManagerInterface $entityManager): Response
    {
        $newType = new TypeArmure();
        $results = $this->createFormTable($newType, TypeArmure::class);
        $typesArmure = $results[0];
        $addTypeForm = $results[1];
        $addTypeForm->handleRequest($request);
        if ($addTypeForm->isSubmitted()) {
            $entityManager->persist($newType);
            $entityManager->flush();
        }
        return $this->render('admin/listTable.html.twig', [
            'list' => $typesArmure,
            'form' => $addTypeForm->createView()
        ]);
    }


    /**
     * Undocumented function
     *
     * @param Object $objet
     * @param string $class
     * @return array
     */
    private function createFormTable(Object $objet, string $class): array
    {
        $repo = $this->getDoctrine()->getRepository($class);
        $findall = $repo->findAll();
        $form = $this->createFormBuilder($objet)
            ->add('nom', TextType::class)
            ->getForm();
        $resArray = [$findall, $form];
        return $resArray;
    }
}
