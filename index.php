<?php
// Función para cargar el archivo de wordlist desde un archivo local o URLs
function loadWordlist($filename, $url1, $url2) {
    $wordlist = [];

    // Cargar desde el archivo local si existe
    if (file_exists($filename)) {
        $localWords = file_get_contents($filename);
        if ($localWords !== FALSE) {
            $wordlist = array_merge($wordlist, explode("\n", $localWords));
        }
    }

    // Cargar desde la primera URL
    $remoteWords1 = file_get_contents($url1);
    if ($remoteWords1 !== FALSE) {
        $wordlist = array_merge($wordlist, explode("\n", $remoteWords1));
    }

    // Cargar desde la segunda URL
    $remoteWords2 = file_get_contents($url2);
    if ($remoteWords2 !== FALSE) {
        $wordlist = array_merge($wordlist, explode("\n", $remoteWords2));
    }

    // Eliminar duplicados y valores vacíos
    $wordlist = array_unique(array_filter(array_map('trim', $wordlist)));

    if (empty($wordlist)) {
        die("Error: No se pudo cargar ninguna palabra desde las fuentes.");
    }

    return $wordlist;
}

// Función para generar palabras válidas a partir de las letras ingresadas
function generateValidWords($letters, $wordlist, $maxWords = 27) {
    $letters = strtolower($letters); // Convertir las letras a minúsculas
    $validWords = [];

    foreach ($wordlist as $word) {
        $word = strtolower(trim($word)); // Limpiar y convertir la palabra a minúsculas
        $wordLength = strlen($word);

        // Verificar si la palabra tiene entre 5 y 6 letras
        if ($wordLength >= 5 && $wordLength <= 6) {
            // Verificar si la palabra se puede formar con las letras ingresadas
            $canFormWord = true;
            $tempLetters = $letters;

            for ($i = 0; $i < $wordLength; $i++) {
                $char = $word[$i];
                $pos = strpos($tempLetters, $char);

                if ($pos === false) {
                    $canFormWord = false;
                    break;
                } else {
                    // Eliminar la letra usada para evitar repeticiones no válidas
                    $tempLetters = substr_replace($tempLetters, '', $pos, 1);
                }
            }

            if ($canFormWord) {
                $validWords[] = $word;

                // Si alcanzamos el límite de palabras, detenernos
                if (count($validWords) >= $maxWords) {
                    break;
                }
            }
        }
    }

    return $validWords;
}

// Función para generar una combinación aleatoria de 6 letras favorables
function generateRandomLetters() {
    // Letras más comunes en inglés, ponderadas por frecuencia (excluyendo J, Q, X, Z)
    $commonLetters = [
        'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', // E es la más común
        'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
        'r', 'r', 'r', 'r', 'r', 'r',
        'i', 'i', 'i', 'i', 'i', 'i',
        'o', 'o', 'o', 'o', 'o', 'o',
        't', 't', 't', 't', 't', 't',
        'n', 'n', 'n', 'n', 'n',
        's', 's', 's', 's', 's',
        'l', 'l', 'l', 'l',
        'c', 'c', 'c', 'c',
        'u', 'u', 'u', 'u',
        'd', 'd', 'd', 'd',
        'p', 'p', 'p',
        'm', 'm', 'm',
        'h', 'h', 'h',
        'g', 'g', 'g',
        'b', 'b',
        'f', 'f',
        'y', 'y',
        'w', 'w',
        'k', 'k',
        'v', 'v'
    ];

    // Seleccionar 6 letras aleatorias de la lista ponderada
    $letters = [];
    for ($i = 0; $i < 6; $i++) {
        $randomIndex = array_rand($commonLetters);
        $letters[] = $commonLetters[$randomIndex];
    }

    return implode('', $letters);
}

// URLs de las wordlists remotas
$url1 = 'https://websites.umich.edu/~jlawler/wordlist';
$url2 = 'https://raw.githubusercontent.com/dwyl/english-words/master/words.txt';

// Cargar el archivo de wordlist desde el archivo local y las URLs
$wordlist = loadWordlist('words_219k.txt', $url1, $url2);

// Verificar si se envió el formulario
$showForm = true;
$jsonOutput = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lucky'])) {
    $combinations = intval($_POST['combinations']); // Obtener la cantidad de combinaciones
    if ($combinations < 1) {
        echo "<p style='color: red;'>Por favor, ingresa un número válido de combinaciones.</p>";
    } else {
        $results = [];
        $attempts = 0;
        while (count($results) < $combinations && $attempts < 1000) { // Límite de intentos para evitar bucles infinitos
            $letters = generateRandomLetters();
            $validWords = generateValidWords($letters, $wordlist);
            if (count($validWords) >= 9) { // Solo incluir combinaciones con al menos 9 palabras válidas
                $results[strtoupper($letters)] = $validWords;
            }
            $attempts++;
        }
        $jsonOutput = json_encode($results, JSON_PRETTY_PRINT);
        $showForm = false; // Ocultar el formulario después de generar las palabras
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador de Palabras</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 1.5rem;
        }

        .combinations-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 1rem;
        }

        input[type="number"] {
            width: 60px;
            padding: 0.5rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s ease;
        }

        input[type="number"]:focus {
            border-color: #007bff;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        .code-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 1rem;
            color: #333;
            text-align: left;
            max-height: 400px; /* Aumentar la altura máxima */
            overflow-y: auto;
            border: 1px solid #ddd;
            margin: 1.5rem 0;
            white-space: pre-wrap; /* Mantener el formato de lista */
        }

        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hidden {
            display: none;
        }
    </style>
    <script>
        function copyToClipboard() {
            const codeBox = document.getElementById('codeBox');
            const range = document.createRange();
            range.selectNode(codeBox);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand('copy');
            alert('JSON copiado al portapapeles.');
        }

        function triggerConfetti() {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
            setTimeout(() => {
                confetti.reset();
            }, 3000); // Detener el confeti después de 3 segundos
        }

        // Ejecutar confeti cuando se muestren las palabras generadas
        window.onload = () => {
            if (document.getElementById('codeBox')) {
                triggerConfetti();
            }
        };
    </script>
</head>
<body>
    <div class="container">
        <?php if ($showForm): ?>
            <h1>Generador de Palabras</h1>
            <form method="POST">
                <div class="combinations-container">
                    <input type="number" id="combinations" name="combinations" min="1" value="1" required>
                    <button type="submit" name="lucky" id="luckyButton">Voy a tener suerte</button>
                </div>
                <div id="loader" class="loader hidden"></div>
            </form>
        <?php else: ?>
            <h2>Palabras generadas:</h2>
            <div class="code-box" id="codeBox">
                <pre><?php echo $jsonOutput; ?></pre>
            </div>
            <button onclick="copyToClipboard()">Copiar JSON</button>
            <button onclick="window.location.href = window.location.href;">Volver a generar</button>
        <?php endif; ?>
    </div>
</body>
</html>