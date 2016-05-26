<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 3/1/2016
 * Time: 02:00 PM
 */
namespace Site\UploadBundle\UpbeatTraits;


trait UpbeatUploadCollectionAdminTrait
{
    public function prePersist($entity) {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->manageEmbeddedFiles($entity);
        $this->fileHandler->clearUploadDir();
    }
    public function preUpdate($entity) {

        $this->prePersist($entity);
    }
    private function manageEmbeddedFiles($entity){
        foreach ($this->getFormFieldDescriptions() as $fieldName => $fieldDescription)
        {
            if ($fieldDescription->getType() === 'sonata_type_collection')
            {
                foreach($this->childAdmins as $admin){
                    if($admin->fieldName == $fieldDescription->getName()){
                        $fileAdmin=$this->container->get($admin->adminService);
                        $getter='get'.ucfirst($admin->fieldName);
                        if(count($entity->$getter())>0){
                            foreach($entity->$getter() as $file){
                                $fileAdmin->manageEmbeddedImageAdmins($file);
                            }
                        }
                    }
                }
            }
        }
    }
}