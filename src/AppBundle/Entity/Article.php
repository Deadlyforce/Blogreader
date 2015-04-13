<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Article
 *
 * @ORM\Table(name="articles")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ArticleRepository")
 */
class Article
{
    /**
     * @var Blog 
     * 
     * @ORM\ManyToOne(targetEntity="Blog", inversedBy="articles")
     * @ORM\JoinColumn(name="blog_id", referencedColumnName="id")
     */
    private $blog;
    
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
     * @ORM\Column(name="titre", type="string", length=255, nullable=true)
     * 
     * @Assert\NotBlank(message="Merci de compléter le champ titre.")
     */
    private $titre;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     * 
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="image_principale", type="string", length=255, nullable=true)
     */
    private $imagePrincipale;

    /**
     * @var string
     *
     * @ORM\Column(name="contenu", type="text", nullable=true)
     * 
     * @Assert\NotBlank(message="Merci de compléter le contenu.")
     */
    private $contenu;
    
    /**
     * @var string 
     * @ORM\Column(name="source", type="text", nullable=false)
     */
    private $source;


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
     * Set titre
     *
     * @param string $titre
     * @return Article
     */
    public function setTitre($titre)
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * Get titre
     *
     * @return string 
     */
    public function getTitre()
    {
        return $this->titre;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Article
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set imagePrincipale
     *
     * @param string $imagePrincipale
     * @return Article
     */
    public function setImagePrincipale($imagePrincipale)
    {
        $this->imagePrincipale = $imagePrincipale;

        return $this;
    }

    /**
     * Get imagePrincipale
     *
     * @return string 
     */
    public function getImagePrincipale()
    {
        return $this->imagePrincipale;
    }

    /**
     * Set contenu
     *
     * @param string $contenu
     * @return Article
     */
    public function setContenu($contenu)
    {
        $this->contenu = $contenu;

        return $this;
    }

    /**
     * Get contenu
     *
     * @return string 
     */
    public function getContenu()
    {
        return $this->contenu;
    }

    /**
     * Set blog
     *
     * @param \AppBundle\Entity\Blog $blog
     * @return Article
     */
    public function setBlog(\AppBundle\Entity\Blog $blog = null)
    {
        $this->blog = $blog;

        return $this;
    }

    /**
     * Get blog
     *
     * @return \AppBundle\Entity\Blog 
     */
    public function getBlog()
    {
        return $this->blog;
    }
    
    /**
     * Set source
     * 
     * @param string $source
     * @return \AppBundle\Entity\Article
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }
    
    /**
     * Get source of the document
     * 
     * @return string
     */
    public function getSource()
    {
        return $this->source;
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
        return 'uploads/images';
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
     * Chemin relatif vers l'image Principale de l'article
     * 
     * @return NULL|string
     *      Relative path.
     */
    public function getImagePrincipaleWeb()
    {
        return NULL === $this->getImagePrincipale()
                ? NULL
                : $this->getUploadPath().'/'.$this->getImagePrincipale();
    }
    
    /**
     * Chemin absolu vers l'image Principale de l'article
     * 
     * @return NULL|string
     *      Absolute path.
     */
    public function getImagePrincipaleAbsolute()
    {
        return NULL === $this->getImagePrincipale()
                ? NULL
                : $this->getUploadAbsolutePath().'/'.$this->getImagePrincipale();
    }
    
    /**
     * Variable temporaire réservée à l'upload
     * 
     * @Assert\File(maxSize="1000000")
     * @var type 
     */
    private $file;
    
    /**
     * Sets file
     * 
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     */
    public function setFile(UploadedFile $file = NULL)
    {
        $this->file = $file;
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
     * Upload une image principale d'un article     * 
     */
    public function upload(){
        // File property can be empty
        if($this->getFile() === NULL){
            return;
        }
        
        $filename = $this->getFile()->getClientOriginalName();
        
        // Move the uploaded file to target directory using original name.
        $this->getFile()->move(
                $this->getUploadAbsolutePath(),
                $filename
                );
        
        // Set the image principale
        $this->setImagePrincipale($filename);
        
        // Cleanup
        $this->setFile();
        
    }
}
