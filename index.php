<?php
// Sample data from the JSON file (simulated for simplicity here)
$database = json_decode(file_get_contents('database.json'), true);

// Get the request URI and parameters
$request = $_SERVER["REQUEST_URI"];
$param = explode("/", $request);

// Determine the method (GET, POST, PUT, DELETE)
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

// Function to handle GET requests
function handleGetRequest($param, $database) {
    if (isset($param[3]) && $param[3] === 'artist') {
        if (!isset($param[4])) {
            // Route: GET /artist - List all artists
            echo json_encode($database['artisti']);
        } elseif (is_numeric($param[4])) {
            // Route: GET /artist/{idArtist} - Get artist by ID
            foreach ($database['artisti'] as $artist) {
                if ($artist['id'] == $param[4]) {
                    echo json_encode($artist);
                    return;
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Artist not found"]);
        } elseif ($param[4] === 'search' && isset($param[5])) {
            // Route: GET /artist/search/{tag} - Search artist by tag
            $tag = strtolower($param[5]);
            $found = [];
            foreach ($database['artisti'] as $artist) {
                foreach ($artist['tags'] as $artistTag) {
                    if (strtolower($artistTag['nome']) === $tag) {
                        $found[] = $artist;
                    }
                }
            }
            echo json_encode($found);
        }
    }
}

// Handle POST request to add a new artist
function handlePostRequest($database) {
    // Route: POST /artist - Add new artist
    $input_text = file_get_contents('php://input');
    echo $input_text;
    $input = json_decode($input_text, true);
    $newArtist = [
        "id" => count($database['artisti']) + 1,
        "nome" => $input['nome'],
        "cognome" => $input['cognome'],
        "nomeDArte" => $input['nomeDArte'],
        "dataDiNascita" => $input['dataDiNascita'],
        "descrizione" => $input['descrizione'],
        "album" => [],
        "tags" => []
    ];
    $database['artisti'][] = $newArtist;
    file_put_contents('database.json', json_encode($database, JSON_PRETTY_PRINT));
    echo json_encode(["message" => "Artist added", "artist" => $newArtist]);
}

// Handle PUT request to edit an artist
function handlePutRequest($param, $database) {
    // Route: PUT /artist/{idArtist} - Update an artist by ID
    if (isset($param[4]) && is_numeric($param[4])) {
        $input_text = file_get_contents('php://input');
        $input = json_decode($input_text, true);
        
        // Search for the artist by ID
        foreach ($database['artisti'] as $index => $artist) {
            if ($artist['id'] == $param[4]) {
                // Update the artist's information with the new data provided in the input
                $database['artisti'][$index]['nome'] = $input['nome'] ?? $artist['nome'];
                $database['artisti'][$index]['cognome'] = $input['cognome'] ?? $artist['cognome'];
                $database['artisti'][$index]['nomeDArte'] = $input['nomeDArte'] ?? $artist['nomeDArte'];
                $database['artisti'][$index]['dataDiNascita'] = $input['dataDiNascita'] ?? $artist['dataDiNascita'];
                $database['artisti'][$index]['descrizione'] = $input['descrizione'] ?? $artist['descrizione'];
                // You can handle album and tags updates if needed similarly.

                // Save the updated database back to the file
                file_put_contents('database.json', json_encode($database, JSON_PRETTY_PRINT));
                
                // Return the updated artist
                echo json_encode([
                    "message" => "Artist updated",
                    "artist" => $database['artisti'][$index]
                ]);
                return;
            }
        }
        
        // If the artist was not found, return a 404 error
        http_response_code(404);
        echo json_encode(["message" => "Artist not found"]);
    } else {
        // If the artist ID was not provided or is invalid
        http_response_code(400);
        echo json_encode(["message" => "Invalid Artist ID"]);
    }
}

// Handle DELETE request to remove an artist
function handleDeleteRequest($param, $database) {
    // Route: DELETE /artist/{idArtist} - Delete an artist
    if (isset($param[4]) && is_numeric($param[4])) {
        foreach ($database['artisti'] as $index => $artist) {
            if ($artist['id'] == $param[4]) {
                unset($database['artisti'][$index]);
                file_put_contents('database.json', json_encode($database, JSON_PRETTY_PRINT));
                echo json_encode(["message" => "Artist deleted"]);
                return;
            }
        }
        http_response_code(404);
        echo json_encode(["message" => "Artist not found"]);
    }
}

// Routing based on HTTP method
switch ($method) {
    case 'GET':
        handleGetRequest($param, $database);
        break;
    case 'POST':
        handlePostRequest($database);
        break;
	case 'PUT':
        handlePutRequest($param, $database);
        break;
    case 'DELETE':
        handleDeleteRequest($param, $database);
        break;
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method Not Allowed"]);
        break;
}