<?php
/**
 * Patient Service
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Victor Kofia <victor.kofia@gmail.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2017 Victor Kofia <victor.kofia@gmail.com>
 * @copyright Copyright (c) 2018 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */


namespace OpenEMR\Services;

use Particle\Validator\Validator;

class PatientService
{

    /**
     * In the case where a patient doesn't have a picture uploaded,
     * this value will be returned so that the document controller
     * can return an empty response.
     */
    private $patient_picture_fallback_id = -1;

    private $pid;

    /**
     * Default constructor.
     */
    public function __construct()
    {
    }

    public function validate($patient)
    {
        $validator = new Validator();

        $validator->required('fname')->lengthBetween(2, 255);
        $validator->required('lname')->lengthBetween(2, 255);
        $validator->required('sex')->lengthBetween(4, 30);
        $validator->required('DOB')->datetime('Y-m-d');


        return $validator->validate($patient);
    }

    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    public function getPid()
    {
        return $this->pid;
    }

    /**
     * TODO: This should go in the ChartTrackerService and doesn't have to be static.
     * @param $pid unique patient id
     * @return recordset
     */
    public static function getChartTrackerInformationActivity($pid)
    {
        $sql = "SELECT ct.ct_when,
                   ct.ct_userid,
                   ct.ct_location,
                   u.username,
                   u.fname,
                   u.mname,
                   u.lname
            FROM chart_tracker AS ct
            LEFT OUTER JOIN users AS u ON u.id = ct.ct_userid
            WHERE ct.ct_pid = ?
            ORDER BY ct.ct_when DESC";
        return sqlStatement($sql, array($pid));
    }

    /**
     * TODO: This should go in the ChartTrackerService and doesn't have to be static.
     * @return recordset
     */
    public static function getChartTrackerInformation()
    {
        $sql = "SELECT ct.ct_when,
                   u.username,
                   u.fname AS ufname,
                   u.mname AS umname,
                   u.lname AS ulname,
                   p.pubpid,
                   p.fname,
                   p.mname,
                   p.lname
            FROM chart_tracker AS ct
            JOIN cttemp ON cttemp.ct_pid = ct.ct_pid AND cttemp.ct_when = ct.ct_when
            LEFT OUTER JOIN users AS u ON u.id = ct.ct_userid
            LEFT OUTER JOIN patient_data AS p ON p.pid = ct.ct_pid
            WHERE ct.ct_userid != 0
            ORDER BY p.pubpid";
        return sqlStatement($sql);
    }

    public function getFreshPid()
    {
        $pid = sqlQuery("SELECT MAX(pid)+1 AS pid FROM patient_data");

        return $pid['pid'] === null ? 1 : $pid['pid'];
    }

    public function insert($data)
    {
        $fresh_pid = $this->getFreshPid();

        $sql = " INSERT INTO patient_data SET";
        $sql .= "     pid=?,";
        $sql .= "     title=?,";
        $sql .= "     fname=?,";
        $sql .= "     mname=?,";
        $sql .= "     lname=?,";
        $sql .= "     street=?,";
        $sql .= "     postal_code=?,";
        $sql .= "     city=?,";
        $sql .= "     state=?,";
        $sql .= "     country_code=?,";
        $sql .= "     phone_contact=?,";
        $sql .= "     DOB=?,";
        $sql .= "     sex=?,";
        $sql .= "     race=?,";
        $sql .= "     ethnicity=?";

        $results = sqlInsert(
            $sql,
            array(
                $fresh_pid,
                $data["title"],
                $data["fname"],
                $data["mname"],
                $data["lname"],
                $data["street"],
                $data["postal_code"],
                $data["city"],
                $data["state"],
                $data["country_code"],
                $data["phone_contact"],
                $data["DOB"],
                $data["sex"],
                $data["race"],
                $data["ethnicity"]
            )
        );

        if ($results) {
            return $fresh_pid;
        }

        return $results;
    }

    public function update($pid, $data)
    {
        $sql = " UPDATE patient_data SET";
        $sql .= "     title=?,";
        $sql .= "     fname=?,";
        $sql .= "     mname=?,";
        $sql .= "     lname=?,";
        $sql .= "     street=?,";
        $sql .= "     postal_code=?,";
        $sql .= "     city=?,";
        $sql .= "     state=?,";
        $sql .= "     country_code=?,";
        $sql .= "     phone_contact=?,";
        $sql .= "     DOB=?,";
        $sql .= "     sex=?,";
        $sql .= "     race=?,";
        $sql .= "     ethnicity=?";
        $sql .= "     where pid=?";

        return sqlStatement(
            $sql,
            array(
                $data["title"],
                $data["fname"],
                $data["mname"],
                $data["lname"],
                $data["street"],
                $data["postal_code"],
                $data["city"],
                $data["state"],
                $data["country_code"],
                $data["phone_contact"],
                $data["DOB"],
                $data["sex"],
                $data["race"],
                $data["ethnicity"],
                $pid
            )
        );
    }

    public function getAll($search)
    {
        $sqlBindArray = array();

        $sql = "SELECT id,
                   pid,
                   pubpid,
                   title,
                   fname,
                   mname,
                   lname,
                   street,
                   postal_code,
                   city,
                   state,
                   country_code,
                   phone_contact,
                   email
                   DOB,
                   sex,
                   race,
                   ethnicity
                FROM patient_data";

        if ($search['name'] || $search['DOB'] || $search['city'] || $search['state'] || $search['postal_code'] || $search['phone_contact'] || $search['address'] || $search['sex'] || $search['country_code']) {
            $sql .= " WHERE ";

            $whereClauses = array();
            if ($search['name']) {
                $search['name'] = '%' . $search['name'] . '%';
                array_push($whereClauses, "CONCAT(lname,' ', fname) LIKE ?");
                array_push($sqlBindArray, $search['name']);
            }
<<<<<<< HEAD
            if ($search['DOB'] || $search['birthdate']) {
                $search['DOB'] = !empty($search['DOB']) ? $search['DOB'] : $search['birthdate'];
                array_push($whereClauses, "DOB=?");
                array_push($sqlBindArray, $search['DOB']);
            }
            if ($search['city']) {
                array_push($whereClauses, "city=?");
                array_push($sqlBindArray, $search['city']);
            }
            if ($search['state']) {
                array_push($whereClauses, "state=?");
                array_push($sqlBindArray, $search['state']);
            }
            if ($search['postal_code']) {
                array_push($whereClauses, "postal_code=?");
                array_push($sqlBindArray, $search['postal_code']);
            }
            if ($search['phone_contact']) {
                array_push($whereClauses, "phone_contact=?");
                array_push($sqlBindArray, $search['phone_contact']);
            }
            if ($search['address']) {
                $search['address'] = '%' . $search['address'] . '%';
                array_push($whereClauses, "city LIKE ? OR street LIKE ? OR state LIKE ? OR postal_code LIKE ?");
                array_push($sqlBindArray, $search['address']);
                array_push($sqlBindArray, $search['address']);
                array_push($sqlBindArray, $search['address']);
                array_push($sqlBindArray, $search['address']);
            }
            if ($search['sex']) {
                array_push($whereClauses, "sex=?");
                array_push($sqlBindArray, $search['sex']);
            }
            if ($search['country_code']) {
                array_push($whereClauses, "country_code=?");
                array_push($sqlBindArray, $search['country_code']);
=======
            if ($search['dob'] || $search['birthdate']) {
                $search['dob'] = !empty($search['dob']) ? $search['dob'] : $search['birthdate'];
                array_push($whereClauses, "dob=?");
                array_push($sqlBindArray, $search['dob']);
>>>>>>> Search by address, phone, gender
            }
            if ($search['city']) {
                array_push($whereClauses, "city=?");
                array_push($sqlBindArray, $search['city']);
            }
            if ($search['state']) {
                array_push($whereClauses, "state=?");
                array_push($sqlBindArray, $search['state']);
            }
            if ($search['postal_code']) {
                array_push($whereClauses, "postal_code=?");
                array_push($sqlBindArray, $search['postal_code']);
            }
            if ($search['phone_contact']) {
                array_push($whereClauses, "phone_contact=?");
                array_push($sqlBindArray, $search['phone_contact']);
            }
            if ($search['address']) {
                $search['address'] = '%' . $search['address'] . '%';
                array_push($whereClauses, "city LIKE ? OR street LIKE ? OR state LIKE ? OR postal_code LIKE ?");
                array_push($sqlBindArray, $search['address']);
                array_push($sqlBindArray, $search['address']);
                array_push($sqlBindArray, $search['address']);
                array_push($sqlBindArray, $search['address']);
            }
            if ($search['sex']) {
                array_push($whereClauses, "sex=?");
                array_push($sqlBindArray, $search['sex']);
            }
            if ($search['country_code']) {
                array_push($whereClauses, "country_code=?");
                array_push($sqlBindArray, $search['country_code']);
            }

            $sql .= implode(" AND ", $whereClauses);
        }

        $statementResults = sqlStatement($sql, $sqlBindArray);
        $results = array();
        while ($row = sqlFetchArray($statementResults)) {
            array_push($results, $row);
        }

        return $results;
    }

    public function getOne()
    {
        $sql = "SELECT id,
                   pid,
                   pubpid,
                   title,
                   fname,
                   mname,
                   lname,
                   street,
                   postal_code,
                   city,
                   state,
                   country_code,
                   phone_contact,
                   email,
                   DOB,
                   sex,
                   race,
                   ethnicity
                FROM patient_data
                WHERE pid = ?";

        return sqlQuery($sql, $this->pid);
    }

    /**
     * @return number
     */
    public function getPatientPictureDocumentId()
    {
        $sql = "SELECT doc.id AS id
                 FROM documents doc
                 JOIN categories_to_documents cate_to_doc
                   ON doc.id = cate_to_doc.document_id
                 JOIN categories cate
                   ON cate.id = cate_to_doc.category_id
                WHERE cate.name LIKE ? and doc.foreign_id = ?";

        $result = sqlQuery($sql, array($GLOBALS['patient_photo_category_name'], $this->pid));

        if (empty($result) || empty($result['id'])) {
            return $this->patient_picture_fallback_id;
        }

        return $result['id'];
    }
}
