<?php

date_default_timezone_set("Asia/Taipei");

// handle any post data
handlePostData();


function handlePostData() {
    // check if there is any post data
    if (empty($_POST))
        exit;
    
    // check if the "action" post data is set
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // call the appropriate function based on the action
        switch ($action) {
            case 'submit_generation':
                // call the function to insert a row of data
                insertData($_POST['user_id'], $_POST['data']);
                break;
            case 'get_unviewed':
                // call the function to get the unviewed data
                $data = getUnviewedData();
                
                // encode the data as JSON
                $jsonData = json_encode($data);
                
                // send the JSON data back to the client
                echo $jsonData;
                break;
                
            case 'get_user_id':
                // call the function to get a random user id
                $id = getNextUserId();
                $data["user_id"] = $id;
                $data["user_color"] = getUserColor($id);
                
                // send the JSON data back to the client
                echo json_encode($data);
                break;
            default:
                // handle any unknown actions here
                break;
        }
    }
}


function getConn() {
    $host = 'localhost'; // assuming the database is hosted on the same server
    $username = 'newyello_re_nft';
    $password = 'thg{c,qsV*cvKr_PNm_Ucl&a';
    $database = 'newyello_recursive';

    // create a connection
    $conn = mysqli_connect($host, $username, $password, $database);

    // check for connection errors
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    return $conn;
}

function insertData($userId, $data) {
    // get the database connection
    $conn = getConn();

    // prepare the SQL statement
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $currentTime = date("Y-m-d H:i:s");

    $sql = "INSERT INTO remote_data (user_id, ip_address, time_stamp, data, is_viewed) VALUES (?, ?, ?, ?, false)";

    // create a prepared statement
    $stmt = mysqli_prepare($conn, $sql);

    // bind the parameters
    mysqli_stmt_bind_param($stmt, "ssss", $userId, $ipAddress, $currentTime, $jsonData);

    // encode the data as JSON
    $jsonData = json_encode($data);

    // execute the statement
    $result = mysqli_stmt_execute($stmt);

    // check for errors
    if (mysqli_errno($conn)) {
        die("Error inserting data: " . mysqli_error($conn));
    }

    // close the statement and connection
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    if($result) {
        echo "Data inserted successfully";
    } else {
        echo "Error inserting data";
    }
}

function getUnviewedData() {
    // get the database connection
    $conn = getConn();

    // prepare the SQL statement
    $sql = "SELECT * FROM remote_data WHERE is_viewed = false";

    // execute the statement
    $result = mysqli_query($conn, $sql);

    // check for errors
    if (!$result) {
        die("Error retrieving unviewed data: " . mysqli_error($conn));
    }

    // loop through the results and store them in an array
    $data = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    // update the records to mark them as viewed
    $sql = "UPDATE remote_data SET is_viewed = true WHERE is_viewed = false";
    mysqli_query($conn, $sql);

    // check for errors
    if (mysqli_errno($conn)) {
        die("Error marking data as viewed: " . mysqli_error($conn));
    }

    // close the connection
    mysqli_close($conn);

    // return the unviewed data
    return $data;
}

function getUserId() {
    $randomId = random(0, 24);
    $hue = 360 / 24 * $randomId;

    $colorCode = hsbToHtmlColor($hue, 0.6, 0.8);

    $returnData["id"] = $randomId;
    $returnData["color"] = $colorCode;

    return json_encode($returnData);
}

function hsbToHtmlColor($hue, $saturation, $brightness) {
    // convert HSB to RGB
    $chroma = $brightness * $saturation;
    $huePrime = $hue / 60;
    $x = $chroma * (1 - abs(fmod($huePrime, 2) - 1));
    $m = $brightness - $chroma;
    
    if ($huePrime < 1) {
        $red = $chroma;
        $green = $x;
        $blue = 0;
    } else if ($huePrime < 2) {
        $red = $x;
        $green = $chroma;
        $blue = 0;
    } else if ($huePrime < 3) {
        $red = 0;
        $green = $chroma;
        $blue = $x;
    } else if ($huePrime < 4) {
        $red = 0;
        $green = $x;
        $blue = $chroma;
    } else if ($huePrime < 5) {
        $red = $x;
        $green = 0;
        $blue = $chroma;
    } else {
        $red = $chroma;
        $green = 0;
        $blue = $x;
    }

    $red = round(($red + $m) * 255);
    $green = round(($green + $m) * 255);
    $blue = round(($blue + $m) * 255);

    // convert RGB to HTML color code
    return sprintf("#%02x%02x%02x", $red, $green, $blue);
}

function getNextUserId() {
    // establish database connection
    $conn = getConn();
    
    // prepare and execute SQL statement to get the highest user_id
    $query = "SELECT MAX(user_id) FROM remote_data";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    
    // close database connection
    $conn->close();
    
    // if no data found, return 0
    if ($row['MAX(user_id)'] == null) {
        return 0;
    }
    
    // return the next available user_id
    return $row['MAX(user_id)'] + 1;
}

function getUserColor ($userId) {
    $hue = 360 / 7 * ($userId % 7);

    if($userId % 14 >= 7)
        $hue += 25;
    
    if($userId % 4 == 0)
    {
        $sat = 0.4;
        $bri = 0.9;
    }
    else if($userId % 4 == 1)
    {
        $sat = 0.4;
        $bri = 0.4;
    }
    else if($userId % 4 == 2)
    {
        $sat = 0.8;
        $bri = 0.9;
    }
    else
    {
        $sat = 0.8;
        $bri = 0.4;
    }

    return hsbToHtmlColor($hue, $sat, $bri);
}


?>