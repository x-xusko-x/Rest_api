<?php

namespace Rest_api\Libraries;

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

/**
 * OpenAPI Request/Response Validator
 * 
 * Validates request bodies and responses against OpenAPI JSON schemas
 */
class OpenAPI_validator
{
    private $validator;
    private $schemas = [];

    public function __construct()
    {
        $this->validator = new Validator();
    }

    /**
     * Validate request data against a schema
     * 
     * @param array $data Request data to validate
     * @param array $schema JSON schema definition
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public function validateRequest(array $data, array $schema): array
    {
        $data_object = json_decode(json_encode($data));
        $schema_object = json_decode(json_encode($schema));

        $this->validator->validate(
            $data_object,
            $schema_object,
            Constraint::CHECK_MODE_APPLY_DEFAULTS
        );

        if ($this->validator->isValid()) {
            return [
                'valid' => true,
                'errors' => []
            ];
        }

        $errors = [];
        foreach ($this->validator->getErrors() as $error) {
            $errors[] = [
                'property' => $error['property'],
                'message' => $error['message'],
                'constraint' => $error['constraint'] ?? null
            ];
        }

        // Reset validator for next validation
        $this->validator->reset();

        return [
            'valid' => false,
            'errors' => $errors
        ];
    }

    /**
     * Validate response data against a schema (for development/testing)
     * 
     * @param mixed $data Response data to validate
     * @param array $schema JSON schema definition
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public function validateResponse($data, array $schema): array
    {
        $data_object = json_decode(json_encode($data));
        $schema_object = json_decode(json_encode($schema));

        $this->validator->validate($data_object, $schema_object);

        if ($this->validator->isValid()) {
            return [
                'valid' => true,
                'errors' => []
            ];
        }

        $errors = [];
        foreach ($this->validator->getErrors() as $error) {
            $errors[] = [
                'property' => $error['property'],
                'message' => $error['message'],
                'constraint' => $error['constraint'] ?? null
            ];
        }

        // Reset validator for next validation
        $this->validator->reset();

        return [
            'valid' => false,
            'errors' => $errors
        ];
    }

    /**
     * Format validation errors for API response
     * 
     * @param array $errors Validation errors from validator
     * @return array Formatted errors for API response
     */
    public function formatValidationErrors(array $errors): array
    {
        $formatted = [];
        
        foreach ($errors as $error) {
            $property = $error['property'];
            $message = $error['message'];
            
            // Remove leading dot from property path
            $property = ltrim($property, '.');
            
            // Convert JSON pointer notation to readable format
            $property = str_replace('.', ' → ', $property);
            
            if (empty($property)) {
                $property = 'request';
            }
            
            $formatted[$property] = $message;
        }
        
        return $formatted;
    }

    /**
     * Get schema definition for a resource
     * 
     * @param string $resource_name Resource name (e.g., 'User', 'Client')
     * @param string $schema_type Schema type ('create', 'update', 'response')
     * @return array|null Schema definition or null if not found
     */
    public function getSchema(string $resource_name, string $schema_type): ?array
    {
        $cache_key = $resource_name . '_' . $schema_type;
        
        if (isset($this->schemas[$cache_key])) {
            return $this->schemas[$cache_key];
        }

        // Try to load schema from Schemas directory
        $schema_class = "Rest_api\\Schemas\\{$resource_name}";
        
        if (class_exists($schema_class)) {
            $method_name = "get{$schema_type}Schema";
            
            if (method_exists($schema_class, $method_name)) {
                $schema = call_user_func([$schema_class, $method_name]);
                $this->schemas[$cache_key] = $schema;
                return $schema;
            }
        }

        return null;
    }

    /**
     * Validate required fields are present
     * 
     * @param array $data Data to validate
     * @param array $required_fields List of required field names
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public function validateRequiredFields(array $data, array $required_fields): array
    {
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $missing_fields[] = $field;
            }
        }

        if (empty($missing_fields)) {
            return [
                'valid' => true,
                'errors' => []
            ];
        }

        $errors = [];
        foreach ($missing_fields as $field) {
            $errors[] = [
                'property' => $field,
                'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required',
                'constraint' => 'required'
            ];
        }

        return [
            'valid' => false,
            'errors' => $errors
        ];
    }

    /**
     * Validate field types
     * 
     * @param array $data Data to validate
     * @param array $field_types Map of field names to expected types
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public function validateTypes(array $data, array $field_types): array
    {
        $errors = [];

        foreach ($field_types as $field => $expected_type) {
            if (!isset($data[$field])) {
                continue; // Skip validation if field is not present
            }

            $value = $data[$field];
            $actual_type = gettype($value);
            $valid = false;

            switch ($expected_type) {
                case 'string':
                    $valid = is_string($value);
                    break;
                case 'integer':
                case 'int':
                    $valid = is_int($value) || (is_string($value) && ctype_digit($value));
                    break;
                case 'number':
                case 'float':
                case 'double':
                    $valid = is_numeric($value);
                    break;
                case 'boolean':
                case 'bool':
                    $valid = is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true);
                    break;
                case 'array':
                    $valid = is_array($value);
                    break;
                case 'object':
                    $valid = is_object($value) || is_array($value);
                    break;
                case 'email':
                    $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                    break;
                case 'url':
                    $valid = filter_var($value, FILTER_VALIDATE_URL) !== false;
                    break;
                case 'date':
                    $valid = strtotime($value) !== false;
                    break;
                default:
                    $valid = true; // Unknown type, skip validation
            }

            if (!$valid) {
                $errors[] = [
                    'property' => $field,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . " must be of type {$expected_type}",
                    'constraint' => 'type'
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

