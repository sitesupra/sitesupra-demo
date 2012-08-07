<?php

namespace Supra\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Supra\Form\Transformer\SubmitLabelToBooleanTransformer;

/**
 * Defines submit button
 */
class SubmitType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$modelTransformer = new SubmitLabelToBooleanTransformer();
		$builder->addModelTransformer($modelTransformer);
	}

	public function getName()
	{
		return 'submit';
	}

	/**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
    }

	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
            'required' => false,
            'compound'   => false,
        ));
	}
}
