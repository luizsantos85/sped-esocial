<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../../bootstrap.php';

use JsonSchema\Constraints\Constraint;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;

//S-2221

$evento = 'evtToxic';
$version = 'S_01_02_00';

$jsonSchema = '{
    "title": "evtToxic",
    "type": "object",
    "properties": {
        "sequencial": {
            "required": false,
            "type": ["integer","null"],
            "minimum": 1,
            "maximum": 99999
        },
        "indretif": {
            "required": true,
            "type": "integer",
            "minimum": 1,
            "maximum": 2
        },
        "nrrecibo": {
            "required": false,
            "type": ["string","null"],
            "$ref": "#/definitions/recibo"
        },
        "idevinculo": {
            "required": true,
            "type": "object",
            "properties": {
                "cpftrab": {
                    "required": true,
                    "type": "string",
                    "maxLength": 11,
                    "minLength": 11
                },
                "matricula": {
                    "required": true,
                    "type": ["string"],
                    "maxLength": 30
                }
            }
        },
        "toxicologico": {
            "required": true,
            "type": "object",
            "properties": {
                "dtexam": {
                    "required": true,
                    "type": "string",
                    "pattern": "^(19[0-9][0-9]|2[0-9][0-9][0-9])[-/](0?[1-9]|1[0-2])[-/](0?[1-9]|[12][0-9]|3[01])$"
                },
                "cnpjlab": {
                    "required": true,
                    "type": "string",
                    "pattern": "^[0-9]{14}$"
                },
                "codseqexame": {
                    "required": true,
                    "type": "string",
                    "pattern": "^[A-Za-z]{2}[0-9]{9}$",
                    "maxLength": 11,
                    "minLength": 11
                },
                "nmmed": {
                    "required": true,
                    "type": ["string"],
                    "maxLength": 70
                },
                "nrcrm": {
                    "required": true,
                    "type": ["string"],
                    "maxLength": 10
                },
                "ufcrm": {
                    "required": true,
                    "type": ["string"],
                    "maxLength": 2
                }
            }
        }
    }
}
';

$std = new \stdClass();
$std->sequencial = 1;
$std->indretif = 1;

$std->idevinculo = new \stdClass();
$std->idevinculo->cpftrab = '11111111111';
$std->idevinculo->matricula = '11111111111';

$std->toxicologico = new \stdClass();
$std->toxicologico->dtexam = '2024-08-01';
$std->toxicologico->cnpjlab = '11111111111111';
$std->toxicologico->codseqexame = 'aB000000000';
$std->toxicologico->cpfmed = '12345678901';
$std->toxicologico->nmmed = 'Dr. Teste';
$std->toxicologico->nrcrm = '11111111';
$std->toxicologico->ufcrm = 'aa';

// Schema must be decoded before it can be used for validation
$jsonSchemaObject = json_decode($jsonSchema);
if (empty($jsonSchemaObject)) {
    echo "<h2>Erro de digitação no schema ! Revise</h2>";
    echo "<pre>";
    print_r($jsonSchema);
    echo "</pre>";
    die();
}

// The SchemaStorage can resolve references, loading additional schemas from file as needed, etc.
$schemaStorage = new SchemaStorage();

// This does two things:
// 1) Mutates $jsonSchemaObject to normalize the references (to file://mySchema#/definitions/integerData, etc)
// 2) Tells $schemaStorage that references to file://mySchema... should be resolved by looking in $jsonSchemaObject
$definitions = realpath(__DIR__."/../../../jsonSchemes/definitions.schema");
$schemaStorage->addSchema("file:{$definitions}", $jsonSchemaObject);

// Provide $schemaStorage to the Validator so that references can be resolved during validation
$jsonValidator = new Validator(new Factory($schemaStorage));

// Do validation (use isValid() and getErrors() to check the result)
$jsonValidator->validate(
    $std,
    $jsonSchemaObject
);
//Constraint::CHECK_MODE_COERCE_TYPES  //tenta converter o dado no tipo indicado no schema

if ($jsonValidator->isValid()) {
    echo "The supplied JSON validates against the schema.<br/>";
} else {
    echo "JSON does not validate. Violations:<br/>";
    foreach ($jsonValidator->getErrors() as $error) {
        echo sprintf("[%s] %s<br/>", $error['property'], $error['message']);
    }
    die;
}
//salva se sucesso
file_put_contents("../../../jsonSchemes/v_$version/$evento.schema", $jsonSchema);
