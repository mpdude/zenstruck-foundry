<?php

namespace Zenstruck\Foundry\Bundle\Maker;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MakeFactory extends AbstractMaker
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public static function getCommandName(): string
    {
        return 'make:factory';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Creates a custom factory for a Doctrine entity class')
            ->addArgument('entity', InputArgument::OPTIONAL, 'Entity class to create a factory for')
        ;

        $inputConfig->setArgumentAsNonInteractive('entity');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if ($input->getArgument('entity')) {
            return;
        }

        $argument = $command->getDefinition()->getArgument('entity');
        $entity = $io->choice($argument->getDescription(), $this->entityChoices());

        $input->setArgument('entity', $entity);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $class = $input->getArgument('entity');

        if (!\class_exists($class)) {
            $class = $generator->createClassNameDetails($class, 'Entity\\')->getFullName();
        }

        if (!\class_exists($class)) {
            throw new RuntimeCommandException(\sprintf('Entity "%s" not found.', $input->getArgument('entity')));
        }

        $entity = new \ReflectionClass($class);
        $factory = $generator->createClassNameDetails(
            $entity->getShortName(),
            'Tests\\Factories\\',
            'Factory'
        );

        $repository = new \ReflectionClass($this->managerRegistry->getRepository($entity->getName()));

        if (0 !== \mb_strpos($repository->getName(), $generator->getRootNamespace())) {
            // not using a custom repository
            $repository = null;
        }

        $generator->generateClass(
            $factory->getFullName(),
            __DIR__.'/../Resources/skeleton/Factory.tpl.php',
            [
                'entity' => $entity,
                'repository' => $repository,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new factory and set default values/states.',
            'Find the documentation at https://github.com/zenstruck/foundry#model-factories',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // noop
    }

    private function entityChoices(): array
    {
        // todo remove choices that already have a factory
        $choices = [];

        foreach ($this->managerRegistry->getManagers() as $manager) {
            foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
                $choices[] = $metadata->getName();
            }
        }

        return $choices;
    }
}
