<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Blog
 *
 * @ORM\Table(name="blogs")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BlogRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Blog
{
    /**
     * @var ArrayCollection 
     * 
     * @ORM\OneToMany(targetEntity="Article", mappedBy="blog")
     */
    private $articles;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=255, nullable=false)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="plateforme", type="string", length=255, nullable=true)
     */
    private $plateforme;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="categorie", type="string", length=255, nullable=true)
     */
    private $categorie;

    /**
     * @var string
     *
     * @ORM\Column(name="auteur", type="string", length=255, nullable=true)
     */
    private $auteur;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", length=255, nullable=true)
     */
    private $logo;
 
    /**
     * Date du dernier crawl
     * 
     * @var \Datetime 
     * @ORM\Column(name="last_crawl_date", type="datetime", nullable=true) 
     */
    private $last_crawl_date;
    
    /**
     * Nombre de liens suivis par le crawler
     * 
     * @var int 
     * @ORM\Column(name="links_followed", type="integer", nullable=true)
     */
    private $links_followed;
    
    /**
     * Nombre de documents effectivement reçus (après filtrage)
     * 
     * @var int
     * @ORM\Column(name="docs_received", type="integer", nullable=true) 
     */
    private $docs_received;
    
    /**
     * Durée du crawl process
     * 
     * @var int 
     * @ORM\Column(name="process_runtime", type="integer", nullable=true)
     */
    private $process_runtime;
    
    /**
     * Quantité de données reçues
     * 
     * @var int 
     * @ORM\Column(name="bytes_received", type="integer", nullable=true)
     */
    private $bytes_received;
    
    /**
     *
     * @var json Array
     * 
     * @ORM\Column(name="url_excluded_words", type="json_array", nullable=true) 
     */
    private $url_excluded_words;
    
    /**
     *
     * @var json Array
     * 
     * @ORM\Column(name="url_excluded_endwords", type="json_array", nullable=true) 
     */
    private $url_excluded_endwords;
    
    /**
     * Concerne une règle pour les url finissant par une date de type :  /2014/11/
     * 
     * @var int
     * 
     * @ORM\Column(name="url_excluded_date", type="boolean")
     */
    private $url_excluded_date;
    
    /**
     * Concerne une règle pour les url finissant par une date de type :  /2014/
     * 
     * @var int
     * 
     * @ORM\Column(name="url_excluded_year", type="boolean")
     */
    private $url_excluded_year;
    
    /**
     * @var int
     * 
     * @ORM\Column(name="request_limit", type="integer", nullable=true)
     */
    private $request_limit;
    
    /**
     * Contient temporairement le chemin du logo ($logo)
     * 
     * @var string 
     */
    private $temp;
    
    
    
    
    public function __construct()
    {        
        $this->articles = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set nom
     *
     * @param string $nom
     * @return Blog
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set plateforme
     *
     * @param string $plateforme
     * @return Blog
     */
    public function setPlateforme($plateforme)
    {
        $this->plateforme = $plateforme;

        return $this;
    }

    /**
     * Get plateforme
     *
     * @return string 
     */
    public function getPlateforme()
    {
        return $this->plateforme;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Blog
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set categorie
     *
     * @param string $categorie
     * @return Blog
     */
    public function setCategorie($categorie)
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * Get categorie
     *
     * @return string 
     */
    public function getCategorie()
    {
        return $this->categorie;
    }

    /**
     * Set auteur
     *
     * @param string $auteur
     * @return Blog
     */
    public function setAuteur($auteur)
    {
        $this->auteur = $auteur;

        return $this;
    }

    /**
     * Get auteur
     *
     * @return string 
     */
    public function getAuteur()
    {
        return $this->auteur;
    }

    /**
     * Set logo
     *
     * @param string $logo
     * @return Blog
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return string 
     */
    public function getLogo()
    {
        return $this->logo;
    }    
   
    /**
     * Get last crawl date
     * 
     * @return datetime Date de dernière récupération de l'actuelle liste d'urls
     */
    public function getLastCrawlDate()
    {
        return $this->last_crawl_date;
    }
    
    /**
     * Sets last crawl date
     * 
     * @param datetime $last_crawl_date
     * @return \AppBundle\Entity\Blog
     */
    public function setLastCrawlDate($last_crawl_date)
    {
        $this->last_crawl_date = $last_crawl_date;        
        return $this;
    }
    
    /**
     * Get number of followed links
     * 
     * @return int
     */
    public function getLinksFollowed()
    {
        return $this->links_followed;
    }
    
    /**
     * Sets number of followed links
     * 
     * @param int $links_followed
     * @return \AppBundle\Entity\Blog
     */
    public function setLinksFollowed($links_followed)
    {
        $this->links_followed = $links_followed;        
        return $this;
    }
    
    /**
     * Get number of docs received
     * 
     * @return int
     */
    public function getDocsReceived()
    {
        return $this->docs_received;
    }
    
    /**
     * Sets number of docs received
     * 
     * @param int $docs_received
     * @return \AppBundle\Entity\Blog
     */
    public function setDocsReceived($docs_received)
    {
        $this->docs_received = $docs_received;        
        return $this;
    }
    
    /**
     * Get crawl process runtime
     * 
     * @return int
     */
    public function getProcessRuntime()
    {
        return $this->process_runtime;
    }
    
    /**
     * Sets process runtime
     * 
     * @param int $process_runtime
     * @return \AppBundle\Entity\Blog
     */
    public function setProcessRuntime($process_runtime)
    {
        $this->process_runtime = $process_runtime;        
        return $this;
    }
    
    /**
     * Get crawl bytes received
     * 
     * @return int
     */
    public function getBytesReceived()
    {
        return $this->bytes_received;
    }
    
    /**
     * Sets bytes received
     * 
     * @param int $bytes_received
     * @return \AppBundle\Entity\Blog
     */
    public function setBytesReceived($bytes_received)
    {
        $this->bytes_received = $bytes_received;        
        return $this;
    }
    
    /**
     * Set url_excluded_words
     * 
     * @param json array $url_excluded_words
     */
    public function setUrlExcludedWords($url_excluded_words)
    {
        $this->url_excluded_words = $url_excluded_words;
        
        return $this;
    }
    
    /**
     * Get url_excluded_words
     * 
     * @return json Array
     */
    public function getUrlExcludedWords()
    {
        return $this->url_excluded_words;
    }
    
    /**
     * Set url_excluded_endwords
     * 
     * @param json array $url_excluded_endwords
     */
    public function setUrlExcludedEndWords($url_excluded_endwords)
    {
        $this->url_excluded_endwords = $url_excluded_endwords;
        
        return $this;
    }
    
    /**
     * Get url_excluded_endwords
     * 
     * @return json Array
     */
    public function getUrlExcludedEndWords()
    {
        return $this->url_excluded_endwords;
    }
    
    /**
     * Sets request_limit
     * 
     * @param int $requestLimit
     */
    public function setRequestLimit($requestLimit)
    {
        $this->request_limit = $requestLimit;
    }
    
    /**
     * Get url excluded date
     * 
     * @return int
     */
    public function getUrlExcludedDate()
    {
        return $this->url_excluded_date;
    }
    
    /**
     * Set url excluded date
     * 
     * @param int $url_excluded_date
     * @return \AppBundle\Entity\Blog
     */
    public function setUrlExcludedDate($url_excluded_date)
    {
        $this->url_excluded_date = $url_excluded_date;
        return $this;
    }
    
    /**
     * Get url excluded year
     * 
     * @return int
     */
    public function getUrlExcludedYear()
    {
        return $this->url_excluded_year;
    }
    
    /**
     * Set url excluded year
     * 
     * @param int $url_excluded_year
     * @return \AppBundle\Entity\Blog
     */
    public function setUrlExcludedYear($url_excluded_year)
    {
        $this->url_excluded_year = $url_excluded_year;
        return $this;
    }
    
    /**
     * Get request_limit
     * 
     * @return int
     */
    public function getRequestLimit()
    {
        return $this->request_limit;
    }

    /**
     * Add articles
     *
     * @param \AppBundle\Entity\Article $articles
     * @return Blog
     */
    public function addArticle(\AppBundle\Entity\Article $articles)
    {
        $this->articles[] = $articles;

        return $this;
    }

    /**
     * Remove articles
     *
     * @param \AppBundle\Entity\Article $articles
     */
    public function removeArticle(\AppBundle\Entity\Article $articles)
    {
        $this->articles->removeElement($articles);
    }

    /**
     * Get articles
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getArticles()
    {
        return $this->articles;
    }
    
    /**
     * Return a blog as a string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->getNom();
    }
    
    // HELPER METHODS **********************************************************
    
    /**
     * Chemin relatif vers le dossier uploads
     * 
     * @return string
     *      Relative path.
     */
    protected function getUploadPath()
    {
        return 'uploads/blog_logo';
    }
    
    /**
     * Chemin absolu vers le dossier uploads
     * 
     * @return string
     *      Absolute path.
     */
    protected function getUploadAbsolutePath()
    {
        return __DIR__.'/../../../web/'.$this->getUploadPath();
    }
    
    /**
     * Chemin relatif vers le logo du blog
     * 
     * @return NULL|string
     *      Relative path.
     */
    public function getLogoWeb()
    {
        return NULL === $this->getLogo()
                ? NULL
                : $this->getUploadPath().'/'.$this->getLogo();
    }
    
    /**
     * Chemin absolu vers le logo du blog
     * 
     * @return NULL|string
     *      Absolute path.
     */
    public function getLogoAbsolute()
    {
        return NULL === $this->getLogo()
                ? NULL
                : $this->getUploadAbsolutePath().'/'.$this->getLogo();
    }
    
    /**
     * Variable temporaire réservée à l'upload
     * 
     * @Assert\File(maxSize="1000000")
     * @var type 
     */
    private $file;
    
//    /**
//     * Sets file
//     * 
//     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
//     */
//    public function setFile(UploadedFile $file = NULL)
//    {
//        $this->file = $file;
//    }
    
    /**
     * Sets file
     * 
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     */
    public function setFile(UploadedFile $file = NULL)
    {
        $this->file = $file;
        
        // check if we have an old image path
        if(isset($this->logo)){
            // store the old name to delete after the update
            $this->temp = $this->logo;
            $this->logo = NULL;
        }else{
            $this->logo = 'initial';
        }
    }
    
    /**
     * Gets file
     * 
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }
    
    /**
     * Si le fichier à uploader existe, charger la variable logo avec un nom unique
     * 
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {        
        if(NULL !== $this->getFile()){
            // Generate unique name
            $filename = sha1(uniqid(mt_rand(), TRUE));
            $extension = $this->getFile()->guessExtension();
            $this->logo = $filename.'.'.$extension;
        }
    }
    
    /**
     * Upload le logo d'un blog
     * 
     * @ORM\PostPersist()
     * @ORM\PostUpdate() 
     */
    public function upload()
    {
        // File property can be empty
        if($this->getFile() === NULL)
        {
            return;
        }
        
        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        
        $this->getFile()->move($this->getUploadAbsolutePath(), $this->logo);
        
        // Check if we have an old image
        if(isset($this->temp)){
            // delete the old image
            unlink($this->getUploadAbsolutePath().'/'.$this->temp);
            // Clear the temp image path
            $this->temp = NULL;
        }
                   
        // Cleanup
        $this->file = NULL;        
    }
    
    /**
     * @ORM\PostRemove
     */
    public function removeUpload()
    {
        $file = $this->getLogoAbsolute();
        if($file){
            unlink($file);
        }
    }
}
