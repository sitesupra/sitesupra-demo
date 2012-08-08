<?php

namespace Supra\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Supra\Form\Transformer\DecimalToStringTransformer;

/**
 * Type will allow using "." and "," as decimal separator, ignore spaces
 */
class DecimalType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$viewTransformer = new DecimalToStringTransformer($options['precision'], $options['ignoreSymbols'], $options['decimalSeparatorSymbols']);
		$builder->addViewTransformer($viewTransformer);
	}

	public function getName()
	{
		return 'decimal';
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
            'compound' => false,
            'precision' => 2,
			// whitespaces removed by default
            'ignoreSymbols' => '',
            'decimalSeparatorSymbols' => '.,',
        ));
	}
}
