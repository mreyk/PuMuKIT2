<?php

namespace Pumukit\SchemaBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;

class PumukitInitRepoCommand extends ContainerAwareCommand
{
    private $dm = null;
    private $tagsRepo = null;
    private $rolesRepo = null;

    private $tagsPath = '../Resources/data/tags/';
    private $rolesPath = '../Resources/data/roles/';
    private $permissionProfilesPath = '../Resources/data/permissionprofiles/';

    private $allPermissions;
    private $tagRequiredFields = array('cod', 'tree_parent_cod', 'metatag', 'display', 'name_en');

    protected function configure()
    {
        $this
            ->setName('pumukit:init:repo')
            ->setDescription('Load Pumukit data fixtures to your database')
            ->addArgument('repo', InputArgument::REQUIRED, 'Select the repo to init: tag, role, permissionprofile, all')
            ->addArgument('file', InputArgument::OPTIONAL, 'Input CSV path')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<'EOT'

Command to load a controlled set of data into a database. Useful for init Pumukit environment.

The --force parameter has to be used to actually drop the database.

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->allPermissions = $this->getContainer()->get('pumukitschema.permission')->getAllPermissions();
        $this->pmk2_allLocales = array_unique(array_merge($this->getContainer()->getParameter('pumukit2.locales'), array('en')));
        $repoName = $input->getArgument('repo');

        if ($input->getOption('force')) {
            switch ($repoName) {
            case 'all':
                $errorExecuting = $this->executeTags($input, $output);
                if (-1 === $errorExecuting) {
                    return -1;
                }
                $errorExecuting = $this->executeRoles($input, $output);
                if (-1 === $errorExecuting) {
                    return -1;
                }
                $errorExecuting = $this->executePermissionProfiles($input, $output);
                if (-1 === $errorExecuting) {
                    return -1;
                }
                break;
            case 'tag':
                $errorExecuting = $this->executeTags($input, $output);
                if (-1 === $errorExecuting) {
                    return -1;
                }
                break;
            case 'role':
                $errorExecuting = $this->executeRoles($input, $output);
                if (-1 === $errorExecuting) {
                    return -1;
                }
                break;
            case 'permissionprofile':
                $errorExecuting = $this->executePermissionProfiles($input, $output);
                if (-1 === $errorExecuting) {
                    return -1;
                }
                break;
            }
        } elseif ($repoName == 'tag') {
            $errorExecuting = $this->executeTags($input, $output, false);
            if (-1 === $errorExecuting) {
                return -1;
            }
        } else {
            $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment.');
            $output->writeln('');
            $output->writeln('<info>Would drop the database</info>');
            $output->writeln('Please run the operation with --force to execute and with --repo to chose the repository to initialize.');
            $output->writeln('<error>All data will be lost!</error>');

            return -1;
        }
    }

    protected function executeTags(InputInterface $input, OutputInterface $output, $force = true)
    {
        $this->tagsRepo = $this->dm->getRepository('PumukitSchemaBundle:Tag');

        $finder = new Finder();
        $finder->files()->in(__DIR__.'/'.$this->tagsPath);
        $file = $input->getArgument('file');
        if ((0 == strcmp($file, '')) && (!$finder)) {
            $output->writeln("<error>Tags: There's no data to initialize</error>");

            return -1;
        }
        if ($force) {
            $this->removeTags();
        }
        $root = $this->createRoot();
        $verbose = $input->getOption('verbose');
        if ($file) {
            $this->createFromFile($file, $root, $output, 'tag', $verbose);
        } else {
            foreach ($finder as $tagFile) {
                $this->createFromFile($tagFile, $root, $output, 'tag', $verbose);
            }
        }

        return 0;
    }

    protected function executeRoles(InputInterface $input, OutputInterface $output)
    {
        $this->rolesRepo = $this->dm->getRepository('PumukitSchemaBundle:Role');

        $finder = new Finder();
        $finder->files()->in(__DIR__.'/'.$this->rolesPath);
        $file = $input->getArgument('file');
        if ((0 == strcmp($file, '')) && (!$finder)) {
            $output->writeln("<error>Roles: There's no data to initialize</error>");

            return -1;
        }
        $this->removeRoles();
        if ($file) {
            $this->createFromFile($file, null, $output, 'role');
        } else {
            foreach ($finder as $roleFile) {
                $this->createFromFile($roleFile, null, $output, 'role');
            }
        }

        return 0;
    }

    protected function executePermissionProfiles(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $finder->files()->in(__DIR__.'/'.$this->permissionProfilesPath);
        $file = $input->getArgument('file');
        if ((0 == strcmp($file, '')) && (!$finder)) {
            $output->writeln("<error>PermissionProfiles: There's no data to initialize</error>");

            return -1;
        }
        $this->removePermissionProfiles();
        if ($file) {
            $this->createFromFile($file, null, $output, 'permissionprofile');
        } else {
            foreach ($finder as $permissionProfilesFile) {
                $this->createFromFile($permissionProfilesFile, null, $output, 'permissionprofile');
            }
        }

        return 0;
    }

    protected function removeTags()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')->remove(array());
    }

    protected function removeRoles()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Role')->remove(array());
    }

    protected function removePermissionProfiles()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:PermissionProfile')->remove(array());
    }

    protected function createRoot()
    {
        $root = $this->tagsRepo->findOneByCod('ROOT');
        if (!$root) {
            $root = $this->createTagFromCsvArray(array('id' => null, 'cod' => 'ROOT', 'tree_parent_cod' => null, 'metatag' => 1, 'display' => 0, 'name_en' => 'ROOT'));
        }
        $this->dm->persist($root);
        $this->dm->flush();

        return $root;
    }

    protected function createFromFile($file_route, $root, OutputInterface $output, $repoName, $verbose = false)
    {
        /* NECCESSARY CHECKS*/
        if (!file_exists($file_route)) {
            $output->writeln('<error>'.$repoName.': Error stating '.$file.": File doesn't exist</error>");

            return -1;
        }

        if (($file = fopen($file_route, 'r')) === false) {
            $output->writeln('<error>Error opening '.$file_route.": fopen() returned 'false' </error>");

            return -1;
        }

        if ($repoName == 'tag') {
            //Creates the csvTagHeaders (to be used later)
            if (($csvTagHeaders = fgetcsv($file, 300, ';', '"')) === false) {
                $output->writeln('<error>Error reading first row (csv header) of '.$file_route.": fgetcsv returned 'false' </error>");

                return -1;
            }

            //Checks if the file header has the required fields. (Only for tags)
            $result_diff = array_diff($this->tagRequiredFields, $csvTagHeaders);
            if (count($result_diff) > 0) {
                $output->writeln('<error>Error reading first row (csv header) of '.$file_route.": HEADER doesn't have the required fields: ".print_r($result_diff, true).' </error>');

                return -1;
            }
        }
        /* END CHECKS */

        $fileExtension = pathinfo($file_route, PATHINFO_EXTENSION);
        $ending = substr($fileExtension, -1);
        if (('~' === $ending) || ('#' === $ending)) {
            $output->writeln('<comment>'.$repoName.': Ignoring file '.$file.'</comment>');

            return -1;
        }
        if ($verbose) {
            $output->writeln('<info>Found file: '.realpath($file_route).'</info>');
        }

        $idCodMapping = array();

        $row = 1;
        $importedTags = array();
        while (($currentRow = fgetcsv($file, 300, ';')) !== false) {
            $number = count($currentRow);
            if (('tag' === $repoName) ||
                (('role' === $repoName) && ($number == 7 || $number == 10)) ||
                (('permissionprofile' === $repoName) && ($number == 6))) {
                //Check header rows
                if (trim($currentRow[0]) == 'id') {
                    continue;
                }

                try {
                    switch ($repoName) {
                    case 'tag':
                        $csvTagsArray = array();
                        for ($i = 0; $i < count($currentRow); ++$i) {
                            $key = $csvTagHeaders[ $i ]; // Here we turn the csv into an associative array (Doesn't a csv parsing library do this already?)
                            $csvTagsArray[ $key ] = $currentRow[ $i ];
                        }

                        if (isset($importedTags[ $csvTagsArray[ 'tree_parent_cod' ] ])) {
                            $parent = $importedTags[ $csvTagsArray[ 'tree_parent_cod' ] ];
                        } else {
                            $parent = $this->tagsRepo->findOneByCod($csvTagsArray[ 'tree_parent_cod' ]);
                        }

                        if (!isset($parent)) {
                            $parent = $root;
                        }
                        try {
                            $tag = $this->createTagFromCsvArray($csvTagsArray, $parent);
                            $importedTags[ $tag->getCod() ] = $tag;
                        } catch (\LengthException $e) {
                            if ($verbose) {
                                $output->writeln('<comment>'.$e->getMessage().'</comment>');
                            }
                            continue;
                        }
                        $output->writeln('<info>Tag persisted - new id: '.$tag->getId().' cod: '.$tag->getCod().'</info>');
                        break;
                    case 'role':
                        $role = $this->createRoleFromCsvArray($currentRow);
                        $idCodMapping[$currentRow[0]] = $role;
                        $output->writeln('Role persisted - new id: '.$role->getId().' code: '.$role->getCod());
                        break;
                    case 'permissionprofile':
                        $permissionProfile = $this->createPermissionProfileFromCsvArray($currentRow);
                        $idCodMapping[$currentRow[0]] = $permissionProfile;
                        $output->writeln('PermissionProfile persisted - new id: '.$permissionProfile->getId().' name: '.$permissionProfile->getName());
                        break;
                    }
                } catch (\Exception $e) {
                    $output->writeln('<error>'.$repoName.': '.$e->getMessage().'</error>');
                }
            } else {
                $output->writeln($repoName.': Last valid row = ...');
                $output->writeln("Error: line $row has $number elements");
            }

            if ($verbose && $row % 100 == 0) {
                echo 'Row '.$row."\n";
            }

            $previous_content = $currentRow;
            ++$row;
        }
        fclose($file);
        $this->dm->flush();
    }

    private function createTagFromCsvArray($csvTagsArray, $tag_parent = null)
    {
        if ($tag = $this->tagsRepo->findOneByCod($csvTagsArray[ 'cod' ])) {
            throw new \LengthException('Nothing done - Tag already on DB - id: '.$tag->getId().' cod: '.$tag->getCod());
        }

        $tag = new Tag();
        $tag->setCod($csvTagsArray['cod']);
        $tag->setMetatag($csvTagsArray['metatag']);
        $tag->setDisplay($csvTagsArray['display']);
        if ($tag_parent) {
            $tag->setParent($tag_parent);
        }
        //Get all titles neccessary on PMK.
        foreach ($this->pmk2_allLocales as $locale) {
            $key_name = 'name_'.$locale;
            if (isset($csvTagsArray[ $key_name ])) {
                $tag->setTitle($csvTagsArray[ $key_name ], $locale);
            } else {
                //Default name will be in english
                $tag->setTitle($csvTagsArray[ 'name_en' ], $locale);
            }
        }
        foreach (array_keys($csvTagsArray) as $key) {
            if (preg_match('/property_*/', $key, $matches)) {
                $property_name = str_replace($matches[0], '', $key);
                $tag->setProperty($property_name, $csvTagsArray[ $key ]);
            }
        }

        $this->dm->persist($tag);

        return $tag;
    }

    /**
     * Create Role from CSV array.
     */
    private function createRoleFromCsvArray($csv_array)
    {
        $role = new Role();

        $role->setCod($csv_array[1]);
        $role->setXml($csv_array[2]);
        $role->setDisplay($csv_array[3]);
        // NOTE Take care of csv language order!
        $role->setName($csv_array[4], 'es');
        if (isset($csv_array[5])) {
            $role->setName($csv_array[5], 'gl');
        }
        if (isset($csv_array[6])) {
            $role->setName($csv_array[6], 'en');
        }
        // NOTE Take care of csv language order!
        if (isset($csv_array[7])) {
            $role->setText($csv_array[7], 'es');
        }
        if (isset($csv_array[8])) {
            $role->setText($csv_array[8], 'gl');
        }
        if (isset($csv_array[9])) {
            $role->setText($csv_array[9], 'en');
        }

        $this->dm->persist($role);

        return $role;
    }

    /**
     * Create PermissionProfile from CSV array.
     */
    private function createPermissionProfileFromCsvArray($csv_array)
    {
        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($csv_array[1]);
        $permissionProfile->setSystem($csv_array[2]);
        $permissionProfile->setDefault($csv_array[3]);
        if (($csv_array[4] === PermissionProfile::SCOPE_GLOBAL) ||
            ($csv_array[4] === PermissionProfile::SCOPE_PERSONAL) ||
            ($csv_array[4] === PermissionProfile::SCOPE_NONE)) {
            $permissionProfile->setScope($csv_array[4]);
        }
        foreach (array_filter(preg_split('/[,\s]+/', $csv_array[5])) as $permission) {
            if ($permission === 'none') {
                break;
            } elseif ($permission === 'all') {
                $permissionProfile = $this->addAllPermissions($permissionProfile);
                break;
            } elseif (array_key_exists($permission, $this->allPermissions)) {
                $permissionProfile->addPermission($permission);
            }
        }

        $this->dm->persist($permissionProfile);

        return $permissionProfile;
    }

    private function addAllPermissions(PermissionProfile $permissionProfile)
    {
        foreach ($this->allPermissions as $key => $value) {
            $permissionProfile->addPermission($key);
        }

        return $permissionProfile;
    }
}
