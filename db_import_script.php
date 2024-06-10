<?php
    function sanitize($data, $conn) {
        $sanitized_data = [];
        foreach ($data as $key => $value) {
            $sanitized_data[$conn->real_escape_string($key)] = $conn->real_escape_string($value);
        }
        return $sanitized_data;
    }

    function connectDB()
    {
        // Database configuration
        $servername = "localhost";
        $username = "root";  // change this to your database username
        $password = "";      // change this to your database password
        // $dbname = "test_app";
        $dbname = "test_weasy"; // Test database

        // $conn = new mysqli($servername, $username, $password);
        $conn = new mysqli($servername, $username, $password);
        
        // If it's a valid connection
        if (!$conn->connect_error)
        {   
            // Verify if the database exists
            $result = $conn->query("SHOW DATABASES LIKE '$dbname'");

            if ($result && $result->num_rows > 0) 
            {   
                // If it exists, connect to it
                $conn = new mysqli($servername, $username, $password, $dbname);
            } else {
                // If it doesn't exists, create it
                $result = $conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
                if (!$result) die("Error creating database: " . mysqli_error($conn));
            }
        }

        return $conn;
    }

    // Create connection
    $conn = connectDB();

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    print_r($conn);
    die;

    // URL to fetch data from
    $url = "https://export.weasy.io/plugins/kuantokustaV2.php?token=eDJHbUNoSXY2Ynl1Lzl1V2xEZ3VvZjd1MnF0UHNFa2RvRy91a0Q2Rmg4OD0=";

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if ($response === FALSE) {
        die("cURL Error: " . curl_error($ch));
    }

    // Close cURL
    curl_close($ch);

    // Remove invalid characters from XML
    $invalid_characters = '/[^\x9\xa\x20-\xD7FF\xE000-\xFFFD]/';
    $response = preg_replace($invalid_characters, '', $response);

    libxml_use_internal_errors(true); // Disable libxml errors and allow user to fetch error information as needed
    $xml = simplexml_load_string($response);

    // echo "<pre>Raw Response:\n";
    // echo htmlspecialchars($response);
    // echo "</pre>";

    if ($xml !== false) {
        echo "The response is XML.";
        $debug_array = array();

        foreach($xml->children() as $table)
        {   
            $table_name = $table->getName();
    
            // Prepare SQL to check if table exists
            $sql_check_table = "SHOW TABLES LIKE '$table_name'";
            $result_check_table = $conn->query($sql_check_table);
            
            if ($result_check_table->num_rows == 0) {
                // Table doesn't exist, create table
                $sql_create_table = "CREATE TABLE $table_name (";
                $sql_create_table .= "`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, "; // Add ID field
    
                foreach ($table->children()->children() as $field) {
                    $field_name = $field->getName();
                    $sql_create_table .= "`$field_name` VARCHAR(255), "; // Adjust field type accordingly
                }
                $sql_create_table = rtrim($sql_create_table, ", ");
                $sql_create_table .= ")";
    
                $debug_array[] = $sql_create_table;
    
                // if ($conn->query($sql_create_table) === TRUE) {
                //     echo "Table $table_name created successfully\n";
                // } else {
                //     echo "Error creating table $table_name: " . $conn->error . "\n";
                // }
            }
            // else {
            //     die('Already exist?');
            // }
    
            // Import data into the table
            foreach ($table->children() as $item) {
                $sql_insert = "INSERT INTO $table_name (";
                $sql_values = "VALUES (";
                foreach ($item->children() as $field_name => $value) {
                    $sql_insert .= "`$field_name`, ";
                    $sql_values .= "'" . $conn->real_escape_string($value) . "', ";
                }
                $sql_insert = rtrim($sql_insert, ", ") . ") ";
                $sql_values = rtrim($sql_values, ", ") . ") ";
                $sql = $sql_insert . $sql_values;
    
                $debug_array[] = $sql;
    
                // if ($conn->query($sql) === TRUE) {
                //     echo "Record inserted successfully into $table_name\n";
                // } else {
                //     echo "Error inserting record into $table_name: " . $conn->error . "\n";
                // }
            }
        }
    } else {
        echo "The response isn't XML.";
    }

    echo '<pre>';
    print_r($debug_array);
    echo '</pre>';

    die('<br>...<br>');

    // $encode = json_encode($xml);
    // $decode = json_decode($encode, true);
    // die('Premature end');

    // Decode JSON data
    // $data = json_decode($response, true);

    // Check if the response is JSON
    if (json_decode($response) !== null) {
        echo "The response is JSON.";
        $data = json_decode($response, true); // Decode JSON data
        // Process JSON data as needed
    } else {
        // Check if the response is XML
        libxml_use_internal_errors(true); // Disable libxml errors and allow user to fetch error information as needed
        $xml = simplexml_load_string($response);
        if ($xml !== false) {
            echo "The response is XML.";
            // Process XML data as needed
        } else {
            echo "The response is neither valid JSON nor XML.";
        }
    }

    // // Check for JSON decoding errors
    // if (json_last_error() !== JSON_ERROR_NONE) {
    //     die("JSON decoding error: " . json_last_error_msg());
    // }

    die('.');

    echo "Data inserted successfully";

    // Close connection
    $conn->close();
?>
