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
     * @var json_array
     * 
     * @ORM\Column(name="url_list", type="json_array", nullable=true) 
     */
    private $url_list;
    
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
     * Set url_list
     *
     * @param json_array $url_list
     * @return Blog
     */
    public function setUrlList($url_list)
    {
        $this->url_list = $url_list;

        return $this;
    }
    
    /**
     * Get url_list
     *
     * @return json_array 
     */
    public function getUrlList()
    {
        return $this->url_list;
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
