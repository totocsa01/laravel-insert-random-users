<?php

namespace Totocsa\InsertRandomUsers\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class UsersSeeder extends Seeder
{
    public $numberOfUsers = 0;
    public $uniqueFullName = false;
    public $namesDirectory = 'en';
    public $isFemales = true;
    public $isMales = true;
    public $generatedPasswords = false;
    public $password = '';
    public $emailDomainName = '';
    public $maxRowsPerInsert = 50;
    public $missingMax = 10;

    public $families;
    public $females;
    public $males;
    public $allFullNames = [];
    public $countFemale = 0;
    public $countMale = 0;
    public $countContinue = 0;
    public $insertData = [];
    public $inserted = 0;

    public function __construct()
    {
        $this->numberOfUsers = env('NUMBER_OF_USERS', $this->numberOfUsers);
        $this->uniqueFullName = env('UNIQUE_FULL_NAME', $this->uniqueFullName);
        $this->namesDirectory = env('NAMES_DIRECTORY', $this->namesDirectory);
        $this->isFemales = env('FEMALES', $this->isFemales);
        $this->isMales = env('MALES', $this->isMales);
        $this->generatedPasswords = env('GENERATED_PASSWORDS', $this->generatedPasswords);
        $this->password = Hash::make(env('PASSWORD', $this->password));
        $this->emailDomainName = env('EMAIL_DOMAIN_NAME', $this->emailDomainName);
        $this->maxRowsPerInsert = env('MAX_ROWS_PER_INSERT', $this->maxRowsPerInsert);
        $this->missingMax = env('MISSING_MAX_ATTEMPTS', $this->missingMax);

        $this->families = $this->fileToArray(__DIR__ . "/UsersSeeder_names/{$this->namesDirectory}/family.txt");
        $this->females = $this->fileToArray(__DIR__ . "/UsersSeeder_names/{$this->namesDirectory}/female.txt");
        $this->males = $this->fileToArray(__DIR__ . "/UsersSeeder_names/{$this->namesDirectory}/male.txt");
    }

    public function run(): void
    {
        $messages = $this->messages();
        if (count($messages) === 0) {
            $this->fillAllFullNames($this->numberOfUsers);
            $this->saveAllFullnames();

            if ($this->inserted < $this->numberOfUsers) {
                $this->missingUsers();
            }

            echo "\nFemale: $this->countFemale. Male: $this->countMale. Same names: $this->countContinue. Inserted: $this->inserted.\n";
        } else {
            $this->usage();
            $this->errors($messages);
        }
    }

    public function missingUsers()
    {
        echo "Missing\n";

        $trying = 1;
        while ($this->inserted < $this->numberOfUsers && $trying <= $this->missingMax) {
            $missing = $this->numberOfUsers - $this->inserted;
            echo "Trial $trying. Missing: $missing.\n";

            $this->fillAllFullNames($missing);
            $this->saveAllFullnames();

            $trying++;
        }
    }

    public function usage()
    {
        $output = $this->command->getOutput();

        $output->writeln('<comment>Usage:</comment>');
        echo "  NUMBER_OF_USERS= UNIQUE_FULL_NAME= NAMES_DIRECTORY= FEMALES= MALES= GENERATED_PASSWORDS= PASSWORD= EMAIL_DOMAIN_NAME= MAX_ROWS_PER_INSERT= MISSING_MAX_ATTEMPTS= php artisan db:seed " . str_replace('\\', '\\\\', $this::class) . "\n\n";
        $output->writeln('<comment>Environment variables:</comment>');
        $output->writeln('<info>  NUMBER_OF_USERS - The number of users to insert. Required.</info>');
        $output->writeln('<info>  EMAIL_DOMAIN_NAME - The domain name of the email address. Required.</info>');
        $output->writeln('<info>  UNIQUE_FULL_NAME - Names should be unique. 1 yes, 0 no. Default: 1</info>');
        $output->writeln('<info>  NAMES_DIRECTORY - The name of the directory containing female, male, and family names. en, hu and 1000randomnames.com. Default: en</info>');
        $output->writeln('<info>  FEMALES - Let there be female names. 1 yes, 0 no. Default: 1</info>');
        $output->writeln('<info>  MALES - Let there be male names. 1 yes, 0 no. Default: 1</info>');
        $output->writeln('<info>  GENERATED_PASSWORDS - Generated passwords. 1 yes, 0 no.  Default: 0</info>');
        $output->writeln('<info>  PASSWORD - Everyone\'s password will be the password you set here.</info>');
        $output->writeln('<info>             The hashed passwords will also match. 1 yes, 0 no.  Default: 1</info>');
        $output->writeln('<info>  MAX_ROWS_PER_INSERT - Maximum number of rows in an insert. Min 1. Default: 50.</info>');
        $output->writeln('<info>  MISSING_MAX_ATTEMPTS - The maximum number of attempts to insert missing rows. Min 1. Default: 10.</info>');
    }

    public function errors($messages)
    {
        $output = $this->command->getOutput();

        $output->writeln('<fg=red;options=bold>Errors:');
        foreach ($messages as $k1 => $v1) {
            $output->writeln("  $k1");
            foreach ($v1 as $v2) {
                $output->writeln("    " . (is_string($v2) ? $v2 : $v2['message']));
            }
        }
        $output->write('</>');
    }

    public function messages()
    {
        $attributes = [
            'NUMBER_OF_USERS' => $this->numberOfUsers,
            'UNIQUE_FULL_NAME' => $this->uniqueFullName,
            'EMAIL_DOMAIN_NAME' => $this->emailDomainName,
            'NAMES_DIRECTORY' => $this->namesDirectory,
            'FEMALES' => $this->isFemales,
            'MALES' => $this->isMales,
            'GENERATED_PASSWORDS' => $this->generatedPasswords,
            'PASSWORD' => $this->password,
            'MAX_ROWS_PER_INSERT' => $this->maxRowsPerInsert,
            'MISSING_MAX_ATTEMPTS' => $this->missingMax,
        ];

        $userRules = User::rules();
        $rules = [
            'NUMBER_OF_USERS' => 'required|integer|min:1',
            'NAMES_DIRECTORY' => 'string',
            'UNIQUE_FULL_NAME' => 'boolean',
            'EMAIL_DOMAIN_NAME' => 'required|string',
            'FEMALES' => 'boolean',
            'MALES' => 'boolean',
            'GENERATED_PASSWORDS' => 'boolean',
            'PASSWORD' => $userRules['password'],
            'MAX_ROWS_PER_INSERT' => 'required|integer|min:1',
            'MISSING_MAX_ATTEMPTS' => 'required|integer|min:1',
        ];

        $validator = Validator::make($attributes, $rules);
        $validator->customAttributes = [
            'NUMBER_OF_USERS' => 'NUMBER_OF_USERS',
            'UNIQUE_FULL_NAME' => 'UNIQUE_FULL_NAME',
            'NAMES_DIRECTORY' => 'NAMES_DIRECTORY',
            'FEMALES' => 'FEMALES',
            'MALES' => 'MALES',
            'GENERATED_PASSWORDS' => 'GENERATED_PASSWORDS',
            'PASSWORD' => 'PASSWORD',
            'EMAIL_DOMAIN_NAME' => 'EMAIL_DOMAIN_NAME',
            'MAX_ROWS_PER_INSERT' => 'MAX_ROWS_PER_INSERT',
            'MISSING_MAX_ATTEMPTS' => 'MISSING_MAX_ATTEMPTS',
        ];

        $messages = $validator->messages()->toArray();

        return $messages;
    }

    public function setEmail(&$i1, &$attributes, $data)
    {
        if ($data['count'] == 1) {
            $attributes['email'] = $this->accent2ascii(strtolower(str_replace(' ', '.', "{$attributes['name']}@$this->emailDomainName")));
            $this->insertData[] = $attributes;

            $i1++;
        } else {
            for ($i2 = 1; $i2 <= $data['count']; $i2++) {
                $attributes['email'] = $this->accent2ascii(strtolower(str_replace(' ', '.', "{$attributes['name']}$i2@$this->emailDomainName")));
                $this->insertData[] = $attributes;

                $i1++;
            }
        }
    }

    public function saveAllFullnames()
    {
        $i1 = 0;
        foreach ($this->allFullNames as $fullName => $data) {
            $attributes = [];
            $attributes['name'] = $fullName;
            $attributes['password'] = $this->generatedPasswords ? Hash::make(bin2hex(random_bytes(16))) : $this->password;

            $this->setEmail($i1, $attributes, $data);

            if (count($this->insertData) >= $this->maxRowsPerInsert) {
                $this->insert();
            }
        }

        if (count($this->insertData) > 0) {
            $this->insert();
        }
    }

    public function insert()
    {
        $inserted =  DB::table('users')->insertOrIgnore($this->insertData);
        $this->inserted += $inserted;
        $this->insertData = [];

        if ($inserted > 0) {
            echo "Inserted now: $inserted. Total inserted so far: $this->inserted.\n";
        }
    }

    public function fillAllFullNames($numberOfUsers)
    {
        for ($i = 1; $i <= $numberOfUsers; $i++) {
            $familyName = $this->families[rand(0, count($this->families) - 1)];

            $givenName = $this->getGivenName();
            if ($givenName === false) {
                break;
            }

            $fullName = "$givenName $familyName";

            if ($this->uniqueFullName && isset($this->allFullNames[$fullName])) {
                $this->countContinue++;
                continue;
            }

            if (isset($this->allFullNames[$fullName])) {
                $this->allFullNames[$fullName]['count']++;
                $this->countContinue++;
            } else {
                $this->allFullNames[$fullName] = ['count' => 1];
            }
        }
    }

    public function getGivenName()
    {
        if ($this->isFemales && $this->isMales) {
            $rand = rand();
            if ($rand % 2 == 0) {
                $givenName = $this->females[rand(0, count($this->females) - 1)];
                $this->countFemale++;
            } else {
                $givenName = $this->males[rand(0, count($this->males) - 1)];
                $this->countMale++;
            }
        } elseif ($this->isFemales) {
            $givenName = $this->females[rand(0, count($this->females) - 1)];
            $this->countFemale++;
        } elseif ($this->isMales) {
            $givenName = $this->males[rand(0, count($this->males) - 1)];
            $this->countMale++;
        } else {
            $givenName = false;
        }

        return $givenName;
    }

    public function fileToArray($filename)
    {
        $content = file_get_contents($filename);
        return explode("\n", $content);
    }

    private function accent2ascii(string $str, string $charset = 'utf-8'): string
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères

        return $str;
    }
}
