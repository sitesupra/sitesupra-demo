<?php

namespace Supra\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Supra\Form\Transformer\FileIdToRecordTransformer;
use Supra\Form\Transformer\FileIdListToRecordTransformer;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

/**
 * Field which stores supra file storage file ID
 */
class FileIdType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$viewTransformer = null;
		if ($options['multiple']) {
			$viewTransformer = new FileIdListToRecordTransformer();
		} else {
			$viewTransformer = new FileIdToRecordTransformer();
		}
		$builder->addViewTransformer($viewTransformer);
	}

	public function buildView(FormView $view, FormInterface $form, array $options)
	{
		$view->vars['multiple'] = $options['multiple'];
	}

	/**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'field';
    }

	public function getName()
	{
		return 'file_id';
	}

	public function setDefaultOptions(\Symfony\Component\OptionsResolver\OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
            'multiple' => false,
			'error_bubbling' => false,
        ));
	}

}
