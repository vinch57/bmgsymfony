<?php

namespace BMG\gestBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use BMG\gestBundle\Form\GenreType;
use BMG\gestBundle\Form\GenreEditType;
use BMG\gestBundle\Entity\Genre;

class gererGenresController extends Controller
{
    public function indexAction()
    {
        try{
            $em = $this->getDoctrine()->getManager();
            $repository_genre = $em->getRepository('BMGgestBundle:Genre');
            $listeGenres = $repository_genre->findAll();
            
            return $this->render(
                'BMGgestBundle:genre:listeGenres.html.twig',
                array(
                    'lesGenres' => $listeGenres
                )
            );
        } catch (Exception $ex) {
            $this->addFlash(
                'error',
                'Erreur dans l\'affichage des genres.'
            );
            return $this->render('BMGgestBundle:Default:index.html.twig');
        }
    }
    
    public function consulterAction($id) {
        //variable pour la gestion des erreurs
        $hasErrors = false;
        if($id != "000") {
            //récupérer l'objet Genre correspondant à $id
            $em = $this->getDoctrine()->getManager();
            $repository_genre = $em->getRepository('BMGgestBundle:Genre');
            $leGenre = $repository_genre->find($id);
            if($leGenre === NULL) {
                //flash message
                $this->addFlash(
                    'error',
                    "Ce genre n'existe pas !"
                );
                $hasErrors = true;
            }
        } else {
            //pas d'id dans l'url ni clic sur Valider : c'est anormal
            //un paramètre a été mis apr défault dans la route s'il a été omis : "000"
            //flash message
            $this->addFlash(
                'error',
                "Aucun genre n'a été transmis pour consultation !"
            );
            $hasErrors = true;
        }
        
        if($hasErrors) {
            //Redirection vers la liste des genres
            return $this->redirectToRoute('bmg_lister_genres');
        } else {
            //appeler la vue pour afficher le genre dont le code est $id
            return $this->render(
                'BMGgestBundle:genre:consulterGenre.html.twig',
                array(
                    'leGenre' => $leGenre
                )
            );
        }
    }
    
    public function ajouterAction(Request $request)
    {
        //On instancie un objet Genre
        $genre = new Genre();
        //On crée le formulaire à l'aide de la classe GenreType
        $form = $this->get('form.factory')->create(GenreType::class, $genre);
        $form->handleRequest($request);
        
        //A présent, si les conditions de la ligne suivante sont remplies, $genre contient les champs du formulaire
        if($form->isSubmitted() && $form->isValid()) {
            //le formulaire est valide et on a cliqué sur le outon "Submit" --> POST
            try{
                //enregistrement en base de données
                //le code est mis en majuscule
                $genre->setCodeGenre(strtoupper($genre->getCodeGenre()));
                $em = $this->getDoctrine()->getManager();
                $em->persist($genre);
                $em->flush();
                //flash msg
                $this->addFlash(
                    'notice',
                    'Le genre '.$genre->getCodeGenre().'-'.$genre->getLibGenre().' a bien été ajouté.'
                );
                //On redirige vers la page de visualisation du genre nouvellement créé
                return $this->render(
                    'BMGgestBundle:genre:consulterGenre.html.twig',
                    array(
                        'leGenre' => $genre
                    )
                );
            } catch (Exception $ex) {
                $this->addFlash(
                    'error',
                    'Erreur dans l\'ajout, il existe déjà un genre avec ce code !'
                );
                
                return $this->render(
                    'BMGgestBundle:genre:ajouterGenre.html.twig',
                    array(
                        'form' => $form->createView(),
                    )
                );
            }
        } else {
            //A ce stage, le formulaire n'est pas valide car:
            // - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
            // - Soit la requête est de type POST, mais le formulaire contient des valeurs invalides, donc on l'affiche de nouveau
            // On passe la méthode createView() du formulaire à la vue
            // afin qu'elle puisse afficher le formulaire
            return $this->render(
               'BMGgestBundle:genre:ajouterGenre.html.twig',
                array(
                   'form' => $form->createView()
                )
            );
        }
    }
    
    public function modifierAction($id, Request $request)
    {
        if($id != "000") {
            //L'URL comporte bien un paramètre
            //Récupérer l'objet Genre correspondant à $id
            $id = strtoupper($id);
            $em = $this->getDoctrine()->getManager();
            $repository_genre = $em->getRepository('BMGgestBundle:Genre');
            $leGenre = $repository_genre->find($id);
            if($leGenre == NULL) {
                //pas de genre pour l'id donné en paramètre
                //flash message
                $this->addFlash(
                    'error',
                    'Modification impossible : Le genre '.$id.' n\'existe pas.'
                );
                //Redirection vers la liste des genres
                return $this->redirectToRoute('bmg_lister_genres');
            } else {
                //id correspond à un genre existant
                $form = $this->get('form.factory')->create(GenreEditType::class, $leGenre);
                //l'id correspond à un genre, on peut l'afficher pour le modifier
                if($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
                    //On vient du POST --> modification en base de données
                    //Inutile de persister ici, Doctrine connait déjà notre genre
                    try{
                        $em->flush();
                        //flash message
                        $this->addFlash(
                            'notice',
                            'Le genre '.$id.'-'.$leGenre->getLibGenre().' a été modifié !'
                        );
                        //redirection vers la page de consultation du genre
                        return $this->render(
                            'BMGgestBundle:genre:consulterGenre.html.twig',
                            array(
                                'leGenre' => $leGenre
                            )
                        );
                    } catch (Exception $ex) {
                        $this->addFlash(
                            'error',
                            'Erreur dans la modification !'
                        );
                        return $this->render(
                            'BMGgestBundle:genre:modifierGenre.html.twig',
                            array(
                                'form' => $form->createView()
                            )
                        );
                    }
                } else {
                    //Je viens du GET --> affichage du formulaire pour modifier
                    return $this->render(
                        'BMGgestBundle:genre:modifierGenre.html.twig',
                        array(
                            'form' => $form->createView()
                        )
                    );
                }
            }
        } else {
            //Pas de paramètre $id
            //flash message
            $this->addFlash(
                'error',
                'Modification impossible : pas de genre à modifier !'
            );
            return $this->redirectToRoute('bmg_lister_genres');
        }
    }
    
    public function supprimerAction($id)
    {
        //Si on a un id valide
        if($id != "000") {
            //rechercher si le genre existe
            $em = $this->getDoctrine()->getManager();
            $repository_genre = $em->getRepository('BMGgestBundle:Genre');
            $leGenre = $repository_genre->find($id);
            //Si on ne trouve pas de genre correpondant à l'id donné en paramètre
            if($leGenre == NULL) {
                $this->addFlash(
                    'error',
                    'Le genre portant le code '.$id.' n\'existe pas!'
                );
            } else {
                //rechercher les ouvrages de ce genre            
                $repository_ouvrage = $em->getRepository('BMGgestBundle:Ouvrage');
                $ouvragesDuGenre = $repository_ouvrage->findByGenre($id);
                //Si le genre n'est référencé par aucun ouvrage
                if($ouvragesDuGenre == NULL) {
                    //c'est bon, on peut le supprimer
                    $em->remove($leGenre);
                    $em->flush();
                    //flash message
                    $this->addFlash(
                        'notice',
                        'Le genre '.$id.'-'.$leGenre->getLibGenre().' a été supprimé !'
                    );
                } else {
                    $this->addFlash(
                        'error',
                        'Le genre '.$id.' est référencé par des ouvrages !'
                    );
                }
            }
        } else {
            $this->addFlash(
                'error',
                "Aucun genre n'a été transmis pour suppression !"
            );
        }
        return $this->redirectToRoute('bmg_lister_genres');
    }
}
