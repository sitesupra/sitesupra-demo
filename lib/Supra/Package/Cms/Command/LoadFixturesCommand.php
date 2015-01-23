<?php


namespace Supra\Package\Cms\Command;

use Supra\Core\Console\AbstractCommand;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\Cms\Entity\TemplateLayout;
use Supra\Package\Cms\Entity\TemplateLocalization;
use Supra\Package\CmsAuthentication\Entity\Group;
use Supra\Package\CmsAuthentication\Entity\User;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Doctrine\ORM\EntityManager;

class LoadFixturesCommand extends AbstractCommand
{
    protected $entityMap = array();

    /**
     * @var EntityManager
     */
    protected $em;

    protected function configure()
    {
        $this->setName('supra:fixtures:load')
            ->addArgument('filename', InputArgument::REQUIRED, 'YML file to load fixtures from (relative to root)')
            ->addOption('clear', 'c', InputOption::VALUE_NONE, 'Clears database before loading fixtures');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->container->getDoctrine()->getManager();
        /* @var $em EntityManager */

        $this->em->beginTransaction();

        if ($input->getOption('clear')) {
            foreach (array(
                         'CmsAuthentication:User',
                         'CmsAuthentication:Group',
                         'Cms:TemplateLocalization',
                         'Cms:TemplateLayout',
                         'Cms:Template',
                     ) as $entity) {
                //todo: also clean audit tables here
                $this->em->createQueryBuilder()
                    ->delete($entity)
                    ->getQuery()
                    ->execute();
            }
        }

        //todo: validate it
        $data = Yaml::parse(
            file_get_contents(
                $this->container->getParameter('directories.project_root') .
                '/' .
                $input->getArgument('filename'))
        );

        //we need to maintain creation order
        foreach (array('group', 'user', 'template') as $section) {
            foreach ($data[$section] as $name => $definition) {
                $this->createEntity($section, $name, $definition);
            }
        }

        $this->em->flush();

        $this->em->commit();
    }

    protected function createEntity($section, $name, $data)
    {
        if (!isset($this->entityMap[$section])) {
            $this->entityMap[$section] = array();
        }

        if (isset($this->entityMap[$section][$name])) {
            return $this->entityMap[$section][$name];
        }

        $entity = call_user_func(array($this, 'createEntity'.ucfirst($section)), $data);

        return $this->entityMap[$section][$name] = $entity;
    }

    protected function resolveEntity($section, $name)
    {
        if (isset($this->entityMap[$section]) && isset($this->entityMap[$section][$name])) {
            return $this->entityMap[$section][$name];
        }

        //todo: try to create entity  here
        throw new \Exception(sprintf('Entity "%s" from section "%s" was not found', $name, $section));
    }

    protected function createEntityTemplate($data)
    {
        $template = new Template();
        $this->em->persist($template);

        $layout = new TemplateLayout($data['media']);
        $layout->setLayoutName($data['layoutName']);
        $layout->setTemplate($template);
        $this->em->persist($layout);

        foreach ($data['localizations'] as $locale => $title) {
            $localization = new TemplateLocalization($locale);
            $localization->setTitle($title);
            $localization->setTemplate($template);
            $this->em->persist($localization);
        }

        return $template;
    }

    protected function createEntityUser($data)
    {
        $user = new User();

        $encoder = $this->container['cms_authentication.encoder_factory']->getEncoder($user);

        $user->setName($data['name']);
        $user->setLogin($data['login']);
        $user->setPassword($encoder->encodePassword($data['password'], $user->getSalt()));
        $user->setEmail($data['email']);
        $user->setActive($data['active']);
        $user->setGroup($this->resolveEntity('group', $data['group']));
        $user->setRoles($data['roles']);

        $this->em->persist($user);
    }

    protected function createEntityGroup($data)
    {
        $group = new Group();
        $group->setName($data['name']);
        $group->setIsSuper($data['isSuper']);
        $this->em->persist($group);

        return $group;
    }
}
