Real Time Sphinx behavior for Yii 

## Getting started

Put files to protected/components or extensions directory

### Configuring Model

```php
/**
 * This is the model class for table "posts".
 *
 * The followings are the available columns in table 'posts':
 * @property integer $id
 * @property string $title
 * @property string $content
 */
class Post extends CActiveRecord
{
    // ....... 
    
    public function behaviors()
    {
        return array(
            'RTSphinxBehavior' => array(
                'class'             => 'ext.rt_sphinx_behavior.RTSphinxBehavior'
                'getDataMethod'     => array($this, 'getIndexData'),
                'sphinxIndex'       => 'rt_posts',
                'sphinxDbComponent' => 'sphinxDbComponent', // component name or Yii::app()->sphinxDbComponent
                'allowCallbacks'    => true,
                'disabled'          => false, // on or off 
            ),
        );
    }

    // .....
    /**
     * @return array
     */
    public function getIndexData()
    {       
        return $this->getAttributes(); // or custom query        
    }

}

// custom use

if (($post = Post::model()->findByPk(1)) !== null) {
    $post->insertIndex(); // uses getDataMethod
    // or
    $post->updateIndex(); // uses getDataMethod
    // or
    $post->deleteIndex(); // uses owner pk
}

// or insert index
for ($indexes as $indexData) 
{
    Post::model()->insertIndex($indexData);
    // or
    Post::model()->replaceIndex($indexData);
}

// or multiple delete
Post::model()->deleteIndex(array(1, 2, 4, 3 ,5));



```
