<?php
session_start();
include '../database/config.php';

try {
    // Fetch all movies
    $stmt = $conn->prepare("
        SELECT 
            m.movie_id, 
            m.title, 
            m.description, 
            m.duration, 
            m.release_date, 
            m.genre, 
            m.language, 
            m.director,
            m.cast,
            m.poster_url,
            m.trailer_url,
            m.rating,
            m.status,
            COUNT(DISTINCT s.show_id) AS show_count
        FROM 
            movies m
        LEFT JOIN 
            shows s ON m.movie_id = s.movie_id
        GROUP BY 
            m.movie_id
        ORDER BY 
            m.release_date DESC
    ");
    $stmt->execute();
    $movies = $stmt->fetchAll();

    // Get genres for filter
    $genre_stmt = $conn->prepare("SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL AND genre != '' ORDER BY genre");
    $genre_stmt->execute();
    $genres = $genre_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get languages for filter
    $language_stmt = $conn->prepare("SELECT DISTINCT language FROM movies WHERE language IS NOT NULL AND language != '' ORDER BY language");
    $language_stmt->execute();
    $languages = $language_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Apply filters if set
    $filter_genre = isset($_GET['genre']) ? $_GET['genre'] : '';
    $filter_language = isset($_GET['language']) ? $_GET['language'] : '';
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';

    if (!empty($filter_genre) || !empty($filter_language) || !empty($search_term)) {
        $filter_query = "
            SELECT 
                m.movie_id, 
                m.title, 
                m.description, 
                m.duration, 
                m.release_date, 
                m.genre, 
                m.language, 
                m.director,
                m.cast,
                m.poster_url,
                m.trailer_url,
                m.rating,
                m.status,
                COUNT(DISTINCT s.show_id) AS show_count
            FROM 
                movies m
            LEFT JOIN 
                shows s ON m.movie_id = s.movie_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filter_genre)) {
            $filter_query .= " AND m.genre = ?";
            $params[] = $filter_genre;
        }

        if (!empty($filter_language)) {
            $filter_query .= " AND m.language = ?";
            $params[] = $filter_language;
        }

        if (!empty($search_term)) {
            $filter_query .= " AND (m.title LIKE ? OR m.description LIKE ? OR m.cast LIKE ? OR m.director LIKE ?)";
            $search_param = "%$search_term%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }

        $filter_query .= " GROUP BY m.movie_id ORDER BY m.release_date DESC";
        $filter_stmt = $conn->prepare($filter_query);
        $filter_stmt->execute($params);
        $movies = $filter_stmt->fetchAll();
    }

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
