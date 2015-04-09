<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Formulaire d'ajout de paramètres pour les réglages du crawler
 *
 * @author Norman
 */
class BlogParamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) 
    {
        $builder
            ->add('request_limit', 'integer', array(
                'required' => TRUE,
                'label' => 'Request limit'
            ))
            ->add('url_excluded_words','hidden', array(
                'required' => FALSE
            ))    
            ->add('url_excluded_endwords','hidden', array(
                'required' => FALSE
            ))    
        ;
    }    
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Blog'
        ));
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return 'blogParam';
    }
}
