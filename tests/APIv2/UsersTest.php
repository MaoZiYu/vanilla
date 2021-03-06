<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use Gdn_PasswordHash;

/**
 * Test the /api/v2/users endpoints.
 */
class UsersTest extends AbstractResourceTest {

    /** @var int A value to ensure new records are unique. */
    protected static $recordCounter = 1;

    /** {@inheritdoc} */
    protected $editFields = ['email', 'name'];

    /** {@inheritdoc} */
    protected $patchFields = ['name', 'email', 'photo', 'emailConfirmed', 'bypassSpam'];

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/users';
        $this->record = [
            'name' => null,
            'email' => null
        ];

        parent::__construct($name, $data, $dataName);
    }

    /**
     * {@inheritdoc}
     */
    public function record() {
        $count = static::$recordCounter;
        $name = "user_{$count}";
        $record = [
            'name' => $name,
            'email' => "$name@example.com"
        ];
        static::$recordCounter++;
        return $record;
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyRow(array $row) {
        $row = parent::modifyRow($row);
        foreach ($this->patchFields as $key) {
            $value = $row[$key];
            switch ($key) {
                case 'email':
                    $value = md5($value).'@vanilla.example';
                    break;
                case 'photo':
                    $hash = md5(microtime());
                    $value = "https://vanillicon.com/v2/{$hash}.svg";
                    break;
                case 'emailConfirmed':
                case 'bypassSpam':
                    $value = !$value;
            }
            $row[$key] = $value;
        }
        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function providePutFields() {
        $fields = [
            'ban' => ['ban', true, 'banned'],
//            'verify' => ['verify', true, 'verified'],
        ];
        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function testGetEdit($record = null) {
        $row = $this->testPost();
        $result = parent::testGetEdit($row);
        return $result;
    }

    /**
     * Test PATCH /users/<id> with a full record overwrite.
     */
    public function testPatchFull() {
        $row = $this->testGetEdit();
        $newRow = $this->modifyRow($row);

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            $newRow
        );

        $this->assertEquals(200, $r->getStatusCode());

        // Setting a photo requires the "photo" field, but output schemas use "photoUrl" as a URL to the actual photo. Account for that.
        $newRow['photoUrl'] = $newRow['photo'];
        unset($newRow['photo']);

        $this->assertRowsEqual($newRow, $r->getBody(), true);

        return $r->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function testPost($record = null, array $extra = []) {
        $record = $this->record();
        $fields = [
            'bypassSpam' => true,
            'emailConfirmed' => false,
            'password' => 'vanilla'
        ];
        $result = parent::testPost($record, $fields);
        return $result;
    }
}
