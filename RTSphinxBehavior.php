<?php

/**
 * RTSphinxBehavior
 *
 * @author mlapko
 */
class RTSphinxBehavior extends CActiveRecordBehavior
{
    /**
     * Method for getting index data
     * 
     * @var mixed 
     */
    public $getDataMethod;
    
    /**
     * Sphinx table
     * 
     * @var string 
     */
    public $sphinxIndex = 'rt_table';
    
    /**
     * Sphinx db component name or component instance
     * 
     * @var mixed 
     */
    public $sphinxDbComponent = 'sphinxDb';
    
    /**
     * Enable or disable callbacks
     * 
     * @var boolean 
     */
    public $allowCallbacks = true;
    
    /**
     * Enable or disable behavior
     *  
     * @var boolean
     */
    public $disabled = false;
    
    /**
     *
     * @var SphinxDbCommand 
     */
    protected $_command;

    /**
     * @return SphinxDbCommand
     */
    public function getCommand($sql = null)
    {
        if ($this->_command === null) {
            $conn = is_object($this->sphinxDbComponent) ? $this->sphinxDbComponent : Yii::app()->{$this->sphinxDbComponent};
            $conn->setActive(true);
            $this->_command = new SphinxDbCommand($conn);            
        }
        return $this->_command->setText($sql);
    }
    
    /**
     * Insert index into sphinx
     * @param mixed $data
     * @return boolean 
     */
    public function insertIndex($data = null)
    {        
        if ($this->disabled) {
            return true;
        }        
        if ($data === null) {
            $data = call_user_func($this->getDataMethod);            
        }       
        return $this->getCommand()->insert($this->sphinxIndex, $data);
    }
    
    /**
     * Update sphinx index
     * 
     * @param mixed $data
     * @return boolean 
     */
    public function updateIndex($data = null)
    {
        if ($this->disabled) {
            return true;
        }
        if ($data === null) {
            $data = call_user_func($this->getDataMethod);            
        }
        return $this->getCommand()->replace($this->sphinxIndex, $data);
    }
    
    /**
     * Delete index from sphinx
     * @param null|array $ids 
     * @return integer
     */
    public function deleteIndex($ids = null)
    {
        if ($this->disabled) {
            return true;
        }
        if ($ids === null) {
            $ids = array($this->getOwner()->getPrimaryKey());            
        } elseif (!is_array($ids)) {
            throw new CException('The param "ids" should be array.');
        }
        
        if (count($ids) > 0) {
            return $this->getCommand()->delete($this->sphinxIndex, 'id IN (' . implode(',', $ids) . ')');
        }
        return 0;
    }
    
    /**
     * Responds to {@link CModel::onAfterSave} event.
     * Inserted or updated sphinx index
     *
     * @param CModelEvent $event event parameter
     */
    public function afterSave($event)
    {
        if (!$this->allowCallbacks) {
            return true;
        }
        
        if ($this->getOwner()->getIsNewRecord()) {
            $this->insertIndex();
        } else {
            $this->updateIndex();
        }        
    }
    
    /**
     * Responds to {@link CModel::onAfterDelete} event.
     * Inserted or updated sphinx index
     *
     * @param CModelEvent $event event parameter
     */
    public function afterDelete($event)
    {
        if (!$this->allowCallbacks) {
            return true;
        }
        $this->deleteIndex();    
    }
    
}


class SphinxDbCommand extends CDbCommand
{
    const INSERT_COMMAND = 'INSERT';
    const REPLACE_COMMAND = 'REPLACE';
    
    /**
     * Creates and executes an INSERT SQL statement.
     * The method will properly escape the column names, and bind the values to be inserted.
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column data (name=>value) to be inserted into the table.
     * @return integer number of rows affected by the execution.
     */
    public function insert($table, $columns)
    {
        return $this->_intoCommand($table, $columns, self::INSERT_COMMAND);
    }
    
    /**
     * Creates and executes an REPLACE SQL statement.
     * The method will properly escape the column names, and bind the values to be replaced.
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column data (name=>value) to be inserted into the table.
     * @return integer number of rows affected by the execution.
     */
    public function replace($table, $columns)
    {
        return $this->_intoCommand($table, $columns, self::REPLACE_COMMAND);
    }    
    
    protected function _intoCommand($table, $columns, $type = self::INSERT_COMMAND)
    {
        $params=array();
        $names = array();
        $placeholders = array();
        foreach ($columns as $name => $value) {
            $names[] = $this->getConnection()->quoteColumnName($name);
            if ($value instanceof CDbExpression) {
                $placeholders[] = $value->expression;
                foreach ($value->params as $n => $v)
                    $params[$n] = $v;
            } else {
                $placeholders[] = ':' . $name;
                $params[':' . $name] = $value;
            }
        }
        $sql = $type . ' INTO ' . $this->getConnection()->quoteTableName($table)
            . ' (' . implode(', ', $names) . ') VALUES ('
            . implode(', ', $placeholders) . ')';
        return $this->setText($sql)->execute($params);
    }
}