<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Mime\Part\DataPart;


class BlogController extends AbstractController
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
     * @Route("/blog", name="app_blog")
     */
    public function index(PaginatorInterface $paginator, Request $request): Response
    {
        $donnees = $this->entityManager->getRepository(Article::class)->findAll();

        $articles = $paginator->paginate(
            $donnees,
            $request->query->getInt('page',1), //numéro de la page en cours, 1 par défaut
            4
        );

        return $this->render('blog/index.html.twig', [
            'articles' => $articles
        ]);
    }

    /**
     * @Route("/", name="home")
     */
    public function home(){

        return $this->render('blog/home.html.twig', [
            'title' => "Bienvenue ici les amis!",
            'age' => 31
        ]);
    }

    /**
     * @Route("/getAllArticles", name="table_articles")
     * @return JsonResponse
     */
    public function getAllArticles(): JsonResponse
    {
        $data = [];
        $articles = $this->entityManager->getRepository(Article::class)->findAll();

        foreach ($articles as $key => $article){
            $data[$key]["title"] = $article->getTitle();
            $data[$key]["category"] = $article->getCategory()->__toString();
            $data[$key]["image"] = $article->getImage();
        }

        return $this->json($data);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/blog/new", name="blog_create")
     * @throws TransportExceptionInterface
     */
    public function create(Request $request, EntityManagerInterface $manager, MailerInterface $mailer) {
        $article = new Article();

        $form = $this->createFormBuilder($article)
                    ->add('title',TextType::class,[
                        'attr' => [
                            'placeholder' => 'Title'
                        ],
                        'label' => 'Title'
                    ])
                    ->add('category',EntityType::class, [
                        'class' => Category::class,
                        'choice_label' => 'title',
                        'label' => 'Category'
                    ])
                    ->add('content',TextAreaType::class,[
                        'attr' => [
                            'placeholder' => 'Content'
                        ],
                        'label' => 'Content'
                    ])
                    ->add('imageFile',VichImageType::class,[
                        'attr' => [
                            'placeholder' => 'Image'
                        ],
                        'label' => 'Add.image',
                        'required' => false
                    ])
                    ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $article->setCreatedAt(new DateTime());
            $manager->persist($article);

            $manager->flush();

            $email = (new Email())
                ->from('hello@example.com')
                ->to('you@example.com')
                ->subject('Ajout d\'un nouvel article confirmé');

//                ->html('<p>See Twig integration for better HTML integration!</p>');
//                ->addPart(new DataPart(new File('/images/products/' . $article->getImage())));

            $mailer->send($email);

//            dd($email);
            return $this->redirectToRoute('blog_show', ['id' => $article->getId()]);
        }
        return $this->render('blog/create.html.twig', [
            'formArticle' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/blog/{id}/edit", name="blog_edit")
     */
    public function update(Article $article,Request $request, EntityManagerInterface $manager){

        $form = $this->createFormBuilder($article)
                    ->add('title',TextType::class,[
                        'attr' => [
                            'placeholder' => 'Title'
                        ],
                        'label' => 'Title'
                    ])
                    ->add('category',EntityType::class, [
                        'class' => Category::class,
                        'choice_label' => 'title',
                        'label' => 'Category'
                    ])
                    ->add('content',TextAreaType::class,[
                        'attr' => [
                            'placeholder' => 'Content'
                        ],
                        'label' => 'Content'
                    ])
                    ->add('imageFile',VichImageType::class,[
                        'attr' => [
                            'placeholder' => 'Image'
                        ],
                        'label' => 'Add.image',
                        'required' => false,
                        'allow_delete' => false,
                        'download_uri' => false,
                    ])
                    ->getForm();

        //$form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            return $this->redirectToRoute('blog_show', ['id' => $article->getId()]);
        }

        return $this->render('blog/update.html.twig', [
            'formArticle' => $form->createView(),
            'editMode' => $article->getId() !== null
        ]);
    }

    /**
     * @Route("/blog/{id}", name="blog_show")
     */
    public function show(Article $article, Request $request, EntityManagerInterface $manager){
        $comment = new Comment();
        $user = $this->getUser();

        $form = $this->createFormBuilder($comment)
                    ->add('content',TextareaType::class, [
                        'attr' => [
                        'placeholder' => 'Content'
                        ],
                        'label' => 'Content'
                    ])
                    ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $comment->setCreatedAt(new \DateTime())
                    ->setArticle($article)
                    ->setAuthor($user->getUsername());

            $manager->persist($comment);
            $manager->flush();

            return $this->redirectToRoute('blog_show', ['id' => $article->getId()]);
        }

        return $this->render('blog/show.html.twig', [
            'article' => $article,
            'commentForm' => $form->createView()
        ]);
    }
}
