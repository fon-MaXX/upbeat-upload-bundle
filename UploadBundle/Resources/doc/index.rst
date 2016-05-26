========================================================================================================================
Upload bundle documentation
========================================================================================================================
Chapter 1: Install
========================================================================================================================

!important - this bundle uses Imagick > 6.0

Section 1.1: kernel
------------------------------------------------------------------------------------------------------------------------

As usual, have to add bundle to AppKernel:

new Site\UploadBundle\SiteUploadBundle()

Section 1.2: roots
------------------------------------------------------------------------------------------------------------------------
Add following to your root.yml:

site_upload:
    resource: "@SiteUploadBundle/Resources/config/routing.yml"
    prefix:   /upload-bundle/

Section 1.3: config
------------------------------------------------------------------------------------------------------------------------
then add config to app/config/config.yml like:

twig:
    debug:            "%kernel.debug%"
    strict_variables: "%kernel.debug%"
    form:
        resources:
            - 'SiteUploadBundle:Form:fields.html.twig'

site_upload:
    web_dir: 'web'
    temp_upload_dir: "uploads/temp"
    types:
        default:
            type: file
            format: 'txt, pdf, doc'  #client validation
#list mime types  http://www.webmaster-toolkit.com/mime-types.shtml
            mime_type:
                - 'text/plain'
                - 'application/msword'
                - 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                - 'application/pdf'
            upload_dir: '/uploads'
            max_size: '47612000' #bytes
        img:
            type: image
            mime_type: []  # default
            format: 'jpg,gif,png'
            upload_dir: '/uploads/icons'
            max_size: '4761200' #bytes
            main_action: 'exact_resize'
        service_icon:
            type: image
            mime_type: []  # default
            format: 'jpg,gif,png'
            upload_dir: '/uploads/service'
            max_size: '4761200' #bytes
            thumbnails:
                small:
                   width: 40
                   height: 40
                   action: exact_resize
                   format: png
                   quality: 100
                big:
                   width: 173
                   height: 173
                   action: exact_resize
                   format: png
                   quality: 100
                   watermark: 'watermark-medium.png'
                    padding-x: 5
                    padding-y: 5
                    opacity: 50

Section 1.4: dependencies
------------------------------------------------------------------------------------------------------------------------
include to your layout and sonata layout in backend

    {% include 'SiteUploadBundle:Default:styles.html.twig' %}
    {% include 'SiteUploadBundle:Default:scripts.html.twig' %}


Chapter 2: Config explanation
========================================================================================================================


temp_upload_dir - where to save temporary files after handling

types - custom name of field

upload_dir - name of dir for defined type, used when files are saving in controller, hav to be completed with
entity id

main_action - define this config when use handleCoverImageFile() method

thumbnails - list of thumbnails.Thumbnails names are custom

action - action for image resize. Available actions:

    - exact_resize - resize with given parameters and best fit
    - landscape_resize - image resize`s by given width, height - auto
    - portrait_resize - image resize`s by given height, width - auto
    - exact_crop - if image<given parameters, it resize`s to accept parameters size,then cropped
    - default - resize by given parameters without best fit

watermark - path to watermark file,have to be included to Resources/public/images/watermarks/  directory of bundle.
Add`s to right bottom corner of image.


Chapter 3: How to use: form builder, sonata admin
========================================================================================================================

Section 3.1: Example of usage in form builder
------------------------------------------------------------------------------------------------------------------------
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('files', 'upbeat_upload_type', array(
                'file_type' => 'service_icon',
                'template'  => 'SiteUploadBundle:Upload:product_image.html.twig',
                'extensions' => 'jpg,gif,png',
                'crop'=>true,
                'crop_width'=>200,
                'crop_height'=>300
            ))
        ;
    }

crop_width and crop_height  - sizes of crop frame

As default,crop frame is fixed-size.You are free to overwrite UploadBundle/Resources/views/Upload/_add_crop.html.twig
for you custom needs of crop functionality ore you can include another template.

Example:
   'SiteUploadBundle:Upload:product_image.html.twig'

               {% if crop == true %}
                {%  include "SiteUploadBundle:Upload:_YOUR_OWN_TEMPLATE_" with{
                        'id':imgid,
                        'crop_width':crop_width,
                        'crop_height':crop_height,
                        'container':container,
                        'callback':callback
                        }
                %}
            {% endif %}

Section 3.2: Example of usage in sonata admin form
------------------------------------------------------------------------------------------------------------------------
        $formMapper
            ->add('files', 'upbeat_upload_type', array(
                'file_type' => 'service_icon',
                'template'  => 'SiteUploadBundle:Upload:product_image.html.twig',
                'extensions' => 'jpg,gif,png',
                'crop'=>true,
                'crop_width'=>200,
                'crop_height'=>300
            ))
        ;

Section 3.3: Example of usage in sonata admin form with one to many
------------------------------------------------------------------------------------------------------------------------
        $formMapper
            ->add('files', 'sonata_type_collection', array(
                'label' => 'Фотографии',
                'by_reference' => false
                ),
                array(
                    'edit' => 'inline',
                    'inline' => 'table',
                    'targetEntity'=>'SiteBackendBundle\Entity\File'
            ))
        ;


Chapter 4: How to use: Controller, Sonata admin class
========================================================================================================================

Section 4.1: Example of usage in controller
------------------------------------------------------------------------------------------------------------------------
        $entity=new \Site\BackendBundle\Entity\File;
        $form = $this->createForm(new QuestionType(),$entity);
        $form->handleRequest($request);
        if($form->isValid())
        {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            $children = $form->all();
            $fileHandler = $this->container->get('upbeat_file_upload.handler');
            foreach ($children as $fieldName => $fieldDescription) {
                $name=$fieldDescription->getConfig()->getType()->getName();
                if ($name === 'upbeat_upload_type') {
                    $fieldAttributes=$fieldDescription->getConfig()->getAttributes();
                    $fieldType=$fieldAttributes['data_collector/passed_options']['file_type'];
                    $filePath=$form[$fieldName]->getData();
                    $subDir='/'.$entity->getId();
                    $setter = 'set' . ucfirst($fieldName);
                    $resultRes = $fileHandler->handleFileAndSave($filePath,$subDir);
                    $entity->$setter(json_encode($resultRes));
                }
            }
            $em->persist($entity);
            $em->flush();
        }
        $fileHandler->clearUploadDir();

$resultRes - array of thumbnail-files, $resultRes['default_file']= '/"upload_dir"/"defined sub dir"/"file"',
if defined thumbnails,other elements,like $resultRes['small'],$resultRes['big'] and so on.
In this example result save`s to db as json string

Section 4.2: Example of usage in Sonata admin class
------------------------------------------------------------------------------------------------------------------------

you have to add following methods to your admin class:

Section 4.2.1: sonata admin for entity with only files included
------------------------------------------------------------------------------------------------------------------------
include this trait to your admin class:

    use \Site\UploadBundle\UpbeatTraits\UpbeatUploadAdminFileTrait;

It contains all code for files handling.

Section 4.2.2: sonata admin for entity with only files collection
------------------------------------------------------------------------------------------------------------------------
example:

    public function __construct(
        $code,
        $class,
        $baseControllerName,
        $container = null,
        $entityManager = null,
        $fileHandler=null)
    {
        parent::__construct($code, $class, $baseControllerName);
        $this->container = $container;
        $this->entityManager=$entityManager;
        $this->fileHandler= $fileHandler;
        $this->childAdmins= new \SplObjectStorage();
        $fileAdmin = new \StdClass;
        $fileAdmin->fieldName='files';
        $fileAdmin->adminService='sonata.admin.file';
        $this->childAdmins->attach($fileAdmin);
    }
    use \Site\UploadBundle\UpbeatTraits\UpbeatUploadAdminFileTrait;

You should define all your embedded "sonata_type_collection" as Std class and attach to $this->childAdmins as shown in
previous example.

Section 4.2.3: sonata admin for entity with files and admin collection
------------------------------------------------------------------------------------------------------------------------
example:

        public function __construct(
        $code,
        $class,
        $baseControllerName,
        $container = null,
        $entityManager = null,
        $fileHandler=null)
    {
        parent::__construct($code, $class, $baseControllerName);
        $this->container = $container;
        $this->entityManager=$entityManager;
        $this->fileHandler= $fileHandler;
        $this->childAdmins= new \SplObjectStorage();
        $fileAdmin = new \StdClass;
        $fileAdmin->fieldName='files';
        $fileAdmin->adminService='sonata.admin.file';
        $this->childAdmins->attach($fileAdmin);
    }
    use \Site\UploadBundle\UpbeatTraits\UpbeatUploadAdminFullTrait;

Section 4.3: Event listeners
------------------------------------------------------------------------------------------------------------------------

You should define event listeners for each entity with paths as content.
Example:

    Services:
        file.delete.listener:
        class: Site\BackendBundle\EventListener\FileDeleteListener
        arguments:
            - @upbeat_file_upload.handler
        tags:
            - { name: doctrine.event_listener, event: preRemove }

And class,which handles clearing directory for File entity in this example.

    class FileDeleteListener
    {
        public function __construct(\Site\UploadBundle\Services\FileHandler $fileHandler)
        {
            $this->fileHandler = $fileHandler;
        }
        public function preRemove(LifecycleEventArgs $args)
        {
            $entity = $args->getEntity();

            if (!$entity instanceof File) {
                return;
            }
            $data = @json_decode($entity->getPath(),true);
            if(@$data['default_file']){
                $path=array_slice(explode('/',$data['default_file']),1,-1);
                $str=null;
                foreach($path as $p){
                    $str.='/'.$p;
                }
                $this->fileHandler->clearDirectory($str,true);
            }
    //        $entityManager = $args->getEntityManager();
        }
    }


Chapter 5: How to use: view examples
========================================================================================================================
If you want to customize field view, you should create your own them and define it in your admin class:
    public function getFormTheme()
    {
        return array_merge(
            parent::getFormTheme(),
            array('SiteUploadBundle:SonataTheme:admin.theme.html.twig')
        );
    }

Chapter 5.1: How to use: template example:

    <image src="{{ file|json_decode.file_default }}">


Chapter 6: Available fileHandler methods
========================================================================================================================
