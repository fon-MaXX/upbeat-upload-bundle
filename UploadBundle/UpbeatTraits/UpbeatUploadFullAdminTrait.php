<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2/29/2016
 * Time: 02:06 PM
 */
namespace Site\UploadBundle\UpbeatTraits;

trait UpbeatUploadFullAdminTrait{
    public function prePersist($entity) {
        $this->manageEmbeddedImageAdmins($entity);
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
    /**
     * @param $entity
     */
    public function manageEmbeddedImageAdmins($entity){
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        foreach ($this->getFormFieldDescriptions() as $fieldName => $fieldDescription) {
            if ($fieldDescription->getType() === 'upbeat_upload_type') {
                $getter='get' . ucfirst($fieldName);
                $filePath= $entity->$getter();
                $data = @json_decode($filePath,true);
                $data = @json_decode($data[0],true);
                if ((isset($data["file_type"]))&&(isset($data["path"]))) {
                    $subDir='/'.$entity->getId();
                    $setter = 'set' . ucfirst($fieldName);
                    $resultRes = $this->fileHandler->handleFileAndSave($filePath,$subDir);
                    $entity->$setter(json_encode($resultRes));
                    $this->entityManager->persist($entity);
                    $this->entityManager->flush();
                }
            }
        }
    }
}