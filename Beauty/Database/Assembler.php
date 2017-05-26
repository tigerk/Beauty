<?php

namespace Beauty\Database;


class Assembler
{
    const LIST_COM = 0;
    const LIST_AND = 1;
    const LIST_SET = 2;
    const LIST_VAL = 3;

    private $sql;

    /**
     * @brief 获取sql
     *
     * @return
     */
    public function getSQL()
    {
        return $this->sql;
    }

    public function getSelect($tables, $fields, $conds = NULL, $options = NULL, $appends = NULL)
    {
        $sql = 'SELECT ';

        // 1. options
        if ($options !== NULL) {
            $options = $this->__makeList($options, Assembler::LIST_COM, ' ');
            if (!strlen($options)) {
                $this->sql = NULL;

                return NULL;
            }
            $sql .= "$options ";
        }

        // 2. fields
        $fields = $this->__makeList($fields, Assembler::LIST_COM);
        if (!strlen($fields)) {
            $this->sql = NULL;

            return NULL;
        }
        $sql .= "$fields FROM ";

        // 3. from
        $tables = $this->__makeList($tables, Assembler::LIST_COM);
        if (!strlen($tables)) {
            $this->sql = NULL;

            return NULL;
        }
        $sql .= $tables;

        // 4. conditions
        if ($conds !== NULL) {
            $conds = $this->__makeList($conds, Assembler::LIST_AND);
            if (!strlen($conds)) {
                $this->sql = NULL;

                return NULL;
            }
            $sql .= " WHERE $conds";
        }

        // 5. other append
        if ($appends !== NULL) {
            $appends = $this->__makeList($appends, Assembler::LIST_COM, ' ');
            if (!strlen($appends)) {
                $this->sql = NULL;

                return NULL;
            }
            $sql .= " $appends";
        }

        $this->sql = $sql;

        return $sql;
    }

    public function getInsert($table, $row, $options = NULL, $onDup = NULL)
    {
        $sql = 'INSERT ';

        // 1. options
        if ($options !== NULL) {
            if (is_array($options)) {
                $options = implode(' ', $options);
            }
            $sql .= "$options ";
        }

        // 2. table
        $sql .= "$table SET ";

        // 3. columns and values
        $row = $this->__makeList($row, Assembler::LIST_SET);
        if (!strlen($row)) {
            $this->sql = NULL;

            return NULL;
        }
        $sql .= $row;

        if (!empty($onDup)) {
            $sql .= ' ON DUPLICATE KEY UPDATE ';
            $onDup = $this->__makeList($onDup, Assembler::LIST_SET);
            if (!strlen($onDup)) {
                $this->sql = NULL;

                return NULL;
            }
            $sql .= $onDup;
        }
        $this->sql = $sql;

        return $sql;
    }

    public function getUpdate($table, $row, $conds = NULL, $options = NULL, $appends = NULL)
    {
        if (empty($row)) {
            return NULL;
        }

        return $this->__makeUpdateOrDelete($table, $row, $conds, $options, $appends);
    }

    public function getDelete($table, $conds = NULL, $options = NULL, $appends = NULL)
    {
        return $this->__makeUpdateOrDelete($table, NULL, $conds, $options, $appends);
    }

    private function __makeList($arrList, $type = Assembler::LIST_SET, $cut = ', ')
    {
        if (is_string($arrList)) {
            return $arrList;
        }

        $sql = '';

        // for set in insert and update
        if ($type == Assembler::LIST_SET) {
            foreach ($arrList as $name => $value) {
                if (is_int($name)) {
                    $sql .= "$value, ";
                } else {
                    // if(!is_int($value))
                    if ($value === NULL) {
                        $value = 'NULL';
                    } else {
                        // set value add ''
                        $value = '\'' . $value . '\'';
                    }
                    $sql .= "$name=$value, ";
                }
            }
            $sql = substr($sql, 0, strlen($sql) - 2);
        } // for where cond
        else if ($type == Assembler::LIST_AND) {
            foreach ($arrList as $name => $value) {
                if (is_int($name)) {
                    $sql .= "($value) AND ";
                } else {
                    // if(!is_int($value))
                    if ($value === NULL) {
                        $value = 'NULL';
                    } else {
                        // where conds value add ''
                        $value = '\'' . $value . '\'';
                    }
                    $sql .= "($name $value) AND ";
                }
            }
            $sql = substr($sql, 0, strlen($sql) - 5);
        } // for batch insert values
        else if ($type == Assembler::LIST_VAL) {
            foreach ($arrList as $value) {
                if ($value === NULL) {
                    $value = 'NULL';
                } else {
                    // insert values add ''
                    $value = '\'' . $this->db->escapeString($value) . '\'';
                }
                $sql .= "$value, ";
            }
            $sql = substr($sql, 0, strlen($sql) - 2);
            $sql = '(' . $sql . ')';
        } else {
            $sql = implode($cut, $arrList);
        }

        return $sql;
    }

    private function __makeUpdateOrDelete($table, $row, $conds, $options, $appends)
    {
        // 1. options
        if ($options !== NULL) {
            if (is_array($options)) {
                $options = implode(' ', $options);
            }
            $sql = $options;
        }

        // 2. fields
        // delete
        if (empty($row)) {
            $sql = "DELETE $options FROM $table ";
        } // update
        else {
            $sql = "UPDATE $options $table SET ";
            $row = $this->__makeList($row, Assembler::LIST_SET);
            if (!strlen($row)) {
                $this->sql = NULL;

                return NULL;
            }
            $sql .= "$row ";
        }

        // 3. conditions
        if ($conds !== NULL) {
            $conds = $this->__makeList($conds, Assembler::LIST_AND);
            if (!strlen($conds)) {
                $this->sql = NULL;

                return NULL;
            }
            $sql .= "WHERE $conds ";
        }

        // 4. other append
        if ($appends !== NULL) {
            $appends = $this->__makeList($appends, Assembler::LIST_COM, ' ');
            if (!strlen($appends)) {
                $this->sql = NULL;

                return NULL;
            }
            $sql .= $appends;
        }

        $this->sql = $sql;

        return $sql;
    }
}