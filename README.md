#   Configuration

Add the bundle `Crowd\PttBundle` to the folder `src` and register it in the `AppKernel` by adding this line in the `$bundles` array of the file:

```php
    new Crowd\PttBundle\PttBundle()
```

In the `composer.json` of the project add the following libraries:

```json
    "smottt/wideimage" : "dev-master",
    "tpyo/amazon-s3-php-class" : "dev-master"
```

And run the command `composer update`.

###  Configuration

Create the file `app/config/ptt.yml`

#### Routing

Add an array for the key `bundles`. It should contain an item for each bundle. Every item must have the keys `bundle` and `controllerClassPrefix`. This will create the routing for each controller subclass of `PttController`.

```yml
    bundles:
        -
            bundle : AdminBundle
            controllerClassPrefix : App\AdminBundle\Controller\
```

If the project have different languages you may add "_local" prefix to your routing controller URL with requirements. Follow this example:
```
/**
* @Route("/{_locale}/team/", name="team", requirements={"_locale": "ca|es|en"});
* @Template()
*/
```

#### Admin configuration

For the key `admin` create an array with the configuration properties of the backend. The `sidebar` array contains multiple items, and each item has its keys:

```yml
    admin:
        title : 'Ptt Admin Demo'
        default_url : post_list
        numberOfResultsPerPage : 50
        sidebar :
            -
                path : post_list
                label : 'Posts'
                class : 'demoClass'
                parameters :
                    id : 1
            -
                label : ‘dropdown’
                subSections:
                    -
                        path : role_list
                        label : 'Roles'

```

Both 'class' and 'parameters' are optional

#### Languages

Add a key/value array of languages (if needed) for the key `languages`. The key should be the ISO 639-1 code of the language. The value the label you want to display in the backend.

```yml
    languages :
        ca : 'Català'
        es : Español
        en : English
        fr : French
```

#### S3

If you want to be able to uploads contents to an S3 Amazon Server you'll need to add the key `s3` with all it's required parameters:

```yml
    s3:
        force : true
        accessKey : ******
        secretKey : ******
        bucket : example
        dir : 'example/'
        url : http://example.s3.amazonaws.com
        prodUrl : http://example.cloudfront.net/
```

Force allows 'PttMediaController' to upload images inserted in the body of the contents to the S3.

### SwiftMail

Swift Mailer integrates into any web app written in PHP 5, offering a flexible and elegant object-oriented approach to sending emails with a multitude of features.

You should define the smtp server at parameters.yml:
```yml
    mailer_transport: gmail
    mailer_host: ~
    mailer_user: pau@crowd-studio.com
    mailer_password: ******
```

With this code you can send an email in a controller. You may change the subject, email from, email to. You can define a twig template for the mail or add some plane html.

```php
$message = \Swift_Message::newInstance()
        ->setSubject('Subject goes here')
        ->setFrom('email@from.com')
        ->setTo('email@to.com')
        ->setBody($this->renderView('FrontendBundle:MailTemplates:mailtemplate.html.twig',
                array('name' => $youCanPassVariables)));
        $this->get('mailer')->send($message);
```
### Controller

To use a `PttController` in a controller add a `use` statement and subclass it.

```php
    use Crowd\PttBundle\Controller\PttController;

    class FooController extends PttController {
```

By default, the `PttController` has 3 methods.

####    List

It will list the entities with the same name as the controller. For example, a controller called `FooController` will list the entities called `Foo`.

By default the only field displayed in the list will be `Title`. You can change this by implementing the method `fieldsToList` in your new controller. This method must return a key/value array with the field/label to list.

```php
    protected function fieldsToList()
    {
        return array(
            'title' => 'Title',
            'relatedUrl' => 'URL',
            'published' => 'Published'
            );
    }
```

Implement the method `listTitle` to override the default list title.

```php
    protected function listTitle()
    {
        return 'My new list Entity title';
    }
```

In the list, the entities will be sortered, by default, using the first element of this array. The user can change the sorting by clicking on the row title (if you use the default `Ptt` template).

#### Sortable list
To make a list sortable by drag and drop you should add `_order` field to the entity.

```
-
        name : _order
        type : number
        options :
            label : 'Ordre'
            attr : ~
        validations : ~
```

```
     /**
     * @var integer
     *
     * @ORM\Column(name="_order", type="text")
     */
    private $_order;

    /**
     * Set _order
     *
     * @param integer $_order
     *
     * @return Social
     */
    public function set_Order($_order)
    {
        $this->_order = $_order;

        return $this;
    }

    /**
     * Get _order
     *
     * @return integer
     */
    public function get_Order()
    {
        return $this->_order;
    }
```

####    Delete

It will delete an entity. To decide if the deletion must continue implement the method `continueWithDeletion`. This method must return an array. The first element has to be a `boolean` indication if the deletion has to continue or not, and the second a `string` with the error message (or an empty string if there is not error).

```php
    protected function continueWithDeletion($entity)
    {
        if (//custom validation using $entity) {
            //not valid
            return array(false, 'Hey, this is an error message');
        } else {
            //valid
            return array(true, '');
        }
    }
```

Just before the deletion the method `beforeDeletion` is called. Implement it to delete related entities.

```php
    protected function beforeDeletion($entity)
    {
        //remove entities related to $entity
    }
```

#### Edit

It displays the entity form and all its fields. Read the section **Form** to understand how to create a form.

Implement the method `editTitle` to override the default list title.

```php
    protected function editTitle()
    {
        return 'My new edit Entity title';
    }
```

#### General

You can create your own `listAction`, `editAction` and `cancelAction` methods by implementing them in your controller.

To use a custom template create it inside the folder `Resources/views/Foo/action.html.twig`.

Implement the method `entityInfo` to return basic information about the entity different to the one by default.

```php
    protected function entityInfo()
    {
        return array(
            'simple' => 'Foo',
            'lowercase' => 'foo',
            'plural' => 'foos'
            );
    }
```

The method `userIsRole($role)` returns a boolean indicating if the user has that role or not.

The method `userRole()` returns a string with the user role.

The method 'allowAccess($methodName, $entity = false)' is called before the `listAction`, `editAction` and `deleteAction`. It should return an array with two items: the first one, a boolean indicating if the access is allowed or not. The second one, the error message, if there's one.

### Form

This section covers the creation of the form and its configuration.

#### General methods

By default this is done in the `PttController`. That's why this is only needed if you implement the `editAction` method in your controller.

To create a form access to it as a service:
```php
    $pttForm = $this->get('pttForm');
```
To add the entity use the method `setEntity`

```php
    $pttForm->setEntity($entity);
```

The others methods available are:

```php
    //Returns boolean indicating if the sent data is valid or not
    $pttForm->isValid();

    //Persists and flushes the entity
    $pttForm->save();

    //Returns the success message
    $pttForm->getSuccessMessage();

    //Returns the error message
    $pttForm->getErrorMessage();

    //Returns the html code for the form
    $pttForm->createView();
```

#### How to create a form

Create an entity and make it a subclass of `PttEntity`. Remember to add the `use`statement.

```php
    use Crowd\PttBundle\Form\PttEntity;

    class MyEntity extends PttEntity {
```

This class now has some extra properties. In case you want to create a translatable website this entity must only have the static properties in it. Create another entity with the same name but with the sufix `Trans` for the translatable properties, for example `MyEntityTrans`, and make it a subclass of `PttTransEntity`. Again, you'll need to use the `use` statement.

```php
    use Crowd\PttBundle\Form\PttTransEntity;

    class MyEntityTrans extends PttTranEntity {
```

This will make this entity have some extra properties too.

Proceed to create a YAML file in the folder `Form` inside the same bundle. The file name must be the same name of the entity (in this case `MyEntity.yml`. **Important**: only one file is needed, even if the entity is translatable.

Inside this file, create a key called `static`, and another called `trans` if needed.

There are two other optional keys that you can override for each form: `errorMessage`, which is the error message that is displayed in case the form has errors, and `successMessage`, which is displayed if the form has no error and the content can be saved.

#### Input types

The way of creating inputs/fields works in the same way either if they're translatable or not. Just remember to add it to the `static` or `trans` array depending if they are inside the `MyEntity` entity or the `MyEntityTrans` entity.

##### Default

Each field has this default properties **always**.

```yml
    -
        name : #the name of the property
        type : #type of field
        options : #array with options
        validations : #array with validations // empty by default
        mapped : #indicates if the field is mapped in the form // true by default
```

You can configure each of this properties. Options is the property that is more customized because is the one that allows us to configure the field. Of course, the type and name properties are also very important. The name must match the name in the entity.

By default, there are some available `options`.

The key `label` indicates the label of the field.
The key `attr` is a key/value array where you can add whatever you want. The most famous attr will be `class` and custom `data-something` attributes.

It would look like:

```yml
    options:
        label : 'Label of the field`
        attr :
            class : 'classOfTheInput`
            data-something : 'somethingElse'
```

These are the available validations:
    - not_blank
    - not_empty #works only in selects
    - password
    - email
    - unique
    - number
Inside the yml, you should add it as a key/value array (type of validation / message to display). For example:

```yml
    validations :
        not_blank : 'The field is required`
```

##### Type Text

Set `text` as input type.

You can set the max length with the atribute `maxLength` in `options`

##### Type Autocomplete

Set `autocomplete` as input type.

In `options` you must add the entity (`entity`) and the column (`searchfield`) where data is collected.

You can filter or sort the list with the properties `sortBy` and `filterBy`. Both are key/value arrays that look, for example, like this. Any of both is required.

```yml
    sortBy :
        title : asc
        author : asc
    filterBy :
        eventId : 1
```

##### Type Url

Set `url` as input type.

An input type url doesn't require any extra configuration that the default one.

##### Type Hidden

Set `hidden` as input type.

An input type hidden doesn't require any extra configuration that the default one.

##### Type Number

Set `number` as input type.

An input number doesn't require any extra configuration that the default one.

##### Type Checkbox

Set `checkbox` as input type.

An input checkbox doesn't require any extra configuration that the default one.

##### Type Password

Set `password` as input type.

An input number text doesn't require any extra configuration that the default one.

You only should use it on actual password fields. If you add this field, you **must add** the `salt` property to your entity too. You don't need to add the `salt` entity to the form.

##### Type File

Set `file` as input type.

Inside the `options` array you must add the `type` property. The available types are:
    - image
    - file

**Storage**: Set the key `s3` inside `options` to `true` to upload the file to the S3 server previously configured.

###### Image
Add the property `sizes` and make it an array with the sizes. These sizes will be used to create images. Each subarray has the keys `w` for width and `h` for height. If both are set to 0 the image size will be free.

```yml
    type : image
    sizes :
        -
            w : 100
            h : 100
        -
            w : 200
            h : 200
```

The name of the images will be `w-h-randomNameStoredInTheProperty.jpg`.

###### File
If you choose `file` it will upload the file and keep the extension intact. The name of the file will be `randomNameStoredInTheProperty.jpg`.

##### Type Legend

Set `legend` as input type.

The `label` property inside `options` is the legend displayed. There's no need to add `validations` but it is very important to set `mapped` to `false`.

##### Type Textarea

Set `textarea` as input type.

By default it will display a simple textarea. You can add the property `type`inside the `options` array to configure it. The available options are:
    - markdown

######  Markdown
It will display an advanced markdown editor. You should add the property `data-height` inside the `attr` property inside `options` to set the height of the textarea.

```yml
    attr :
        data-height : 300
    type : markdown
```

##### Type Multiple
Multiple its a sortable array of modules with different layouts. The modules should be declared as a entity with the prefix 'module'. Inside `options` you can declare a default value for the selector at `empty` parameter. You should put the different modules in `modules` tag inside `options` with the `label` that will be displayed at selector and the `entity` who referenced.

```
-
        name : moduleSelector
        type : multipleEntity
        mapped : false
        showErrors : false
        options :
            empty : Selecciona un element de la llista
            label : Mòdul
            modules :
                -
                    label : Text destacat
                    entity : moduleOutstandingText
                -
                    label : Text amb Títol
                    entity : moduleTitleText
                -
                    label : Imatge 100% amplada
                    entity : moduleImage100
                -
                    label : Imatge 80% amplada
                    entity : moduleImage80
                -
                    label : 2 imatges 2 columnes
                    entity : moduleImage2col
```

##### Type Gallery

Set `gallery` as input type.

You must set the `showErrors` and `mapped` to false

Inside `options` you must inform the entity that appears for each image loaded. That entity should have one image field named "image" with option type set to "gallery".

Image field from related entity example:
```yml
    -
        name : image
        type : file
        options :
            label : 'Image'
            attr : ~
            type : gallery
            sizes :
                -
                    w : 600
                    h : 400
                -
                    w : 1400
                    h : 600
                -
                    w : 1000
                    h : 0
        validations : ~
```

Gallery field example:
```yml
    -
        name : galleryImage
        type : gallery
        showErrors : false
        mapped : false
        options :
            label : 'Image'
            entity : GalleryImage
        validations : ~

```
mapped : false

##### Type Select

Set `select` as input type.

It has an optional `empty` property that you can add to the `options` array. It will be the default option in the select.

```yml
    empty : 'Select one option'
```

Inside the `options` array you must add the `type` property. The available types are:
    - static
    - entity

###### static

Add the a key/value array `options` inside the `options` array. These will be the key/value options displayed in the select. For example:

```yml
    empty : Select
    options :
        1 : Header
        2 : Footer
        3 : Others
```

###### entity

The standard configuration is adding the `entity` key inside the `options` array. Add only the name of the entity and make sure that the entity is in the same bundle. You will also have to add the method `__toString` inside the entity so the select can print its name.

This entity will be displayed in the list.

You can filter or sort the list with the properties `sortBy` and `filterBy`. Both are key/value arrays that look, for example, like this. Any of both is required.

```yml
    sortBy :
        title : asc
        author : asc
    filterBy :
        eventId : 1
```

In case you want to create a multiple relation you'll have to create the relating entity. This entity must have 2 fields, one for the current `objectId` and another for the `relatedObjectId`. The names are customizable. To enable that you'll have to add the `multiple` key to the `options` array.

**Important**: if the select is multiple you have to set the `mapped` option of the field to `false`.

The properties inside `multiple are`:
    - relatingEntity : the name of the relating entity
    - me : the property that identifies the id of the current entity (if the form is the Event form, the eventId)
    - them : the property that identifies the id of the related entity (if the form is the Event form, the artistId)

In case we're in a `Event` entity and want to relate it with multiple `Artist` entities, the YAML should look like.

```yml
    entity : Artist
    multiple :
        relatingEntity : EventArtist
        me : eventId
        them : artistId
```

If you want to display that same relation but in the Artist form you'll have to switch the values. In this example it'd look like this.


```yml
    entity : Event
    multiple :
        relatingEntity : EventArtist
        me : artistId
        them : eventId
```

##### Type SelectMultiple

Are two selectors in the second depends on the first.

You must add two more fields to the entity: [field_name]_model and [field_name]_title (the last is optional).

At the YML you should define the number of returned entities and the entities of the first selector.

```yml
    -
        name : slider
        type : selectMultiple
        options :
            label : 'Select Multiple'
            limit : 20
            empty : '-- Selecciona el tipo --'
            entities :
                -
                    entity : Activity
                    label : Actividad
                -
                    entity : Exhibition
                    label : Exposición
                -
                    entity : Publication
                    label : Publicación
``

##### Type Entity

Set `entity` as input type.

Set `showErrors` and `mapped` to false. Inside options fill the property `entity` with the name of the relating entity. That entity must have a `relatedId` property.