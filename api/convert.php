<?php

declare(strict_types=1);

require_once '../vendor/autoload.php';
require_once '../config/database.php';
require_once '../auth/check.php';

header('Content-Type: application/json; charset=utf-8');

ob_start();

/*
|--------------------------------------------------------------------------
| CONFIGURATION
|--------------------------------------------------------------------------
*/

const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB

const PYTHON_SCRIPT =
    __DIR__ . '/../python/converter.py';


/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

function json_response(
    bool $success,
    string $message = '',
    array $data = []
): void {

    if (ob_get_level() > 0) {
        ob_clean();
    }

    header_remove('Content-Type');

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        array_merge(
            [
                'success' => $success,
                'message' => $message
            ],
            $data
        ),
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if (!is_logged_in()) {

    json_response(
        false,
        'غير مصرح لك بالوصول.'
    );
}


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
) {

    json_response(
        false,
        'طريقة الطلب غير صحيحة.'
    );
}


/*
|--------------------------------------------------------------------------
| EXEC CHECK
|--------------------------------------------------------------------------
*/

if (!function_exists('exec')) {

    json_response(
        false,
        'دالة exec() غير مفعلة في PHP.'
    );
}


/*
|--------------------------------------------------------------------------
| FIND PYTHON
|--------------------------------------------------------------------------
|
| لا يوجد أي مسار ثابت.
|
| يتم البحث تلقائيًا عن:
|
| python
| py
| python3
|
|--------------------------------------------------------------------------
*/

function find_python(): string
{
    $commands = [
        'python',
        'py',
        'python3'
    ];

    foreach ($commands as $command) {

        $output = [];

        $exitCode = 0;

        if (PHP_OS_FAMILY === 'Windows') {

            @exec(
                'where ' . $command . ' 2>NUL',
                $output,
                $exitCode
            );

        } else {

            @exec(
                'command -v ' . $command . ' 2>/dev/null',
                $output,
                $exitCode
            );
        }

        if (
            $exitCode !== 0 ||
            empty($output)
        ) {
            continue;
        }

        foreach ($output as $path) {

            $path = trim($path);

            if ($path === '') {
                continue;
            }

            /*
             * في Windows نحتاج ملفًا حقيقيًا.
             */

            if (
                PHP_OS_FAMILY === 'Windows' &&
                !is_file($path)
            ) {
                continue;
            }

            /*
             * Linux / macOS
             */

            if (
                PHP_OS_FAMILY !== 'Windows' &&
                !is_file($path)
            ) {
                continue;
            }

            return $path;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Fallback: جرّب python مباشرة
    |--------------------------------------------------------------------------
    */

    $output = [];

    $exitCode = 0;

    @exec(
        PHP_OS_FAMILY === 'Windows'
            ? 'python --version 2>&1'
            : 'python3 --version 2>&1',
        $output,
        $exitCode
    );

    if ($exitCode === 0) {

        return PHP_OS_FAMILY === 'Windows'
            ? 'python'
            : 'python3';
    }


    throw new RuntimeException(
        'لم يتم العثور على Python في النظام. ' .
        'تأكد أن Python مثبت ومضاف إلى PATH.'
    );
}


/*
|--------------------------------------------------------------------------
| CHECK PYTHON VERSION
|--------------------------------------------------------------------------
*/

function check_python(
    string $python
): void {

    $output = [];

    $exitCode = 0;

    $command =
        escapeshellarg($python) .
        ' --version 2>&1';

    @exec(
        $command,
        $output,
        $exitCode
    );

    if ($exitCode !== 0) {

        throw new RuntimeException(
            'تم العثور على Python لكن تعذر تشغيله.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| CHECK CONVERTER.PY
|--------------------------------------------------------------------------
*/

if (
    !is_file(PYTHON_SCRIPT)
) {

    json_response(
        false,
        'ملف Python غير موجود: ' .
        PYTHON_SCRIPT
    );
}


/*
|--------------------------------------------------------------------------
| PDF FILE
|--------------------------------------------------------------------------
*/

if (
    !isset($_FILES['pdf']) ||
    !is_array($_FILES['pdf'])
) {

    json_response(
        false,
        'لم يتم اختيار ملف PDF.'
    );
}

$file = $_FILES['pdf'];


/*
|--------------------------------------------------------------------------
| UPLOAD ERROR
|--------------------------------------------------------------------------
*/

if (
    !isset($file['error']) ||
    $file['error'] !== UPLOAD_ERR_OK
) {

    $errorCode =
        (int)($file['error'] ?? -1);

    $message = match ($errorCode) {

        UPLOAD_ERR_INI_SIZE =>
            'حجم الملف تجاوز الحد المسموح به من PHP.',

        UPLOAD_ERR_FORM_SIZE =>
            'حجم الملف تجاوز الحد المسموح به.',

        UPLOAD_ERR_PARTIAL =>
            'تم رفع جزء من الملف فقط.',

        UPLOAD_ERR_NO_FILE =>
            'لم يتم رفع أي ملف.',

        UPLOAD_ERR_NO_TMP_DIR =>
            'مجلد الملفات المؤقتة غير موجود.',

        UPLOAD_ERR_CANT_WRITE =>
            'تعذر حفظ الملف على السيرفر.',

        UPLOAD_ERR_EXTENSION =>
            'تم إيقاف رفع الملف بواسطة إضافة PHP.',

        default =>
            'حدث خطأ أثناء رفع الملف.'
    };

    json_response(
        false,
        $message
    );
}


/*
|--------------------------------------------------------------------------
| ORIGINAL NAME
|--------------------------------------------------------------------------
*/

$originalName =
    basename(
        (string)($file['name'] ?? '')
    );


if ($originalName === '') {

    json_response(
        false,
        'اسم الملف غير صالح.'
    );
}


/*
|--------------------------------------------------------------------------
| EXTENSION
|--------------------------------------------------------------------------
*/

$extension =
    strtolower(
        pathinfo(
            $originalName,
            PATHINFO_EXTENSION
        )
    );


if ($extension !== 'pdf') {

    json_response(
        false,
        'يسمح برفع ملفات PDF فقط.'
    );
}


/*
|--------------------------------------------------------------------------
| FILE SIZE
|--------------------------------------------------------------------------
*/

$fileSize =
    (int)($file['size'] ?? 0);


if ($fileSize <= 0) {

    json_response(
        false,
        'الملف فارغ.'
    );
}


if (
    $fileSize > MAX_FILE_SIZE
) {

    json_response(
        false,
        'حجم الملف أكبر من 50MB.'
    );
}


/*
|--------------------------------------------------------------------------
| TEMP FILE
|--------------------------------------------------------------------------
*/

$tmpPath =
    $file['tmp_name'] ?? '';


if (
    $tmpPath === '' ||
    !is_uploaded_file($tmpPath)
) {

    json_response(
        false,
        'ملف الرفع غير صالح.'
    );
}


/*
|--------------------------------------------------------------------------
| MIME CHECK
|--------------------------------------------------------------------------
*/

$finfo =
    finfo_open(
        FILEINFO_MIME_TYPE
    );


if (!$finfo) {

    json_response(
        false,
        'تعذر فحص نوع الملف.'
    );
}


$mime =
    finfo_file(
        $finfo,
        $tmpPath
    );


finfo_close($finfo);


if (
    $mime !== 'application/pdf'
) {

    json_response(
        false,
        'الملف ليس PDF صالحًا.'
    );
}


/*
|--------------------------------------------------------------------------
| CHECK PDF HEADER
|--------------------------------------------------------------------------
*/

$handle =
    @fopen(
        $tmpPath,
        'rb'
    );


if ($handle === false) {

    json_response(
        false,
        'تعذر قراءة ملف PDF.'
    );
}


$pdfHeader =
    fread(
        $handle,
        5
    );


fclose($handle);


if (
    $pdfHeader !== '%PDF-'
) {

    json_response(
        false,
        'الملف لا يحتوي على ترويسة PDF صحيحة.'
    );
}


/*
|--------------------------------------------------------------------------
| DIRECTORIES
|--------------------------------------------------------------------------
*/

$uploadDir =
    __DIR__ . '/../uploads/';

$convertedDir =
    __DIR__ . '/../converted/';


foreach (
    [
        $uploadDir,
        $convertedDir
    ] as $directory
) {

    if (!is_dir($directory)) {

        if (
            !mkdir(
                $directory,
                0755,
                true
            )
        ) {

            json_response(
                false,
                'تعذر إنشاء المجلد: ' .
                $directory
            );
        }
    }

    if (!is_writable($directory)) {

        json_response(
            false,
            'المجلد غير قابل للكتابة: ' .
            $directory
        );
    }
}


/*
|--------------------------------------------------------------------------
| UNIQUE ID
|--------------------------------------------------------------------------
*/

$fileId =
    bin2hex(
        random_bytes(16)
    );


$pdfName =
    $fileId . '.pdf';

$docxName =
    $fileId . '.docx';


$pdfPath =
    $uploadDir .
    $pdfName;

$docxPath =
    $convertedDir .
    $docxName;


/*
|--------------------------------------------------------------------------
| MOVE PDF
|--------------------------------------------------------------------------
*/

if (
    !move_uploaded_file(
        $tmpPath,
        $pdfPath
    )
) {

    json_response(
        false,
        'فشل حفظ ملف PDF.'
    );
}


/*
|--------------------------------------------------------------------------
| CONVERSION
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | FIND PYTHON
    |--------------------------------------------------------------------------
    */

    $python =
        find_python();


    /*
    |--------------------------------------------------------------------------
    | CHECK PYTHON
    |--------------------------------------------------------------------------
    */

    check_python(
        $python
    );


    /*
    |--------------------------------------------------------------------------
    | PYTHON COMMAND
    |--------------------------------------------------------------------------
    */

    $command =
        escapeshellarg(
            $python
        ) .

        ' ' .

        escapeshellarg(
            PYTHON_SCRIPT
        ) .

        ' ' .

        escapeshellarg(
            $pdfPath
        ) .

        ' ' .

        escapeshellarg(
            $docxPath
        ) .

        ' 2>&1';


    /*
    |--------------------------------------------------------------------------
    | EXECUTE PYTHON
    |--------------------------------------------------------------------------
    */

    $output = [];

    $exitCode = 0;


    exec(
        $command,
        $output,
        $exitCode
    );


    /*
    |--------------------------------------------------------------------------
    | PYTHON OUTPUT
    |--------------------------------------------------------------------------
    */

    $pythonOutput =
        trim(
            implode(
                PHP_EOL,
                $output
            )
        );


    /*
    |--------------------------------------------------------------------------
    | PYTHON FAILED
    |--------------------------------------------------------------------------
    */

    if ($exitCode !== 0) {

        throw new RuntimeException(
            'فشل تشغيل محرك Python.' .
            PHP_EOL .
            (
                $pythonOutput !== ''
                    ? $pythonOutput
                    : 'لم يرجع Python أي تفاصيل.'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY DOCX EXISTS
    |--------------------------------------------------------------------------
    */

    clearstatcache(
        true,
        $docxPath
    );


    if (
        !file_exists($docxPath)
    ) {

        throw new RuntimeException(
            'Python انتهى بدون إنشاء ملف Word.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY DOCX SIZE
    |--------------------------------------------------------------------------
    */

    $docxSize =
        filesize(
            $docxPath
        );


    if (
        $docxSize === false ||
        $docxSize <= 0
    ) {

        throw new RuntimeException(
            'ملف Word الناتج فارغ.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY DOCX ZIP SIGNATURE
    |--------------------------------------------------------------------------
    */

    $docxHandle =
        @fopen(
            $docxPath,
            'rb'
        );


    if ($docxHandle === false) {

        throw new RuntimeException(
            'تعذر قراءة ملف Word الناتج.'
        );
    }


    $signature =
        fread(
            $docxHandle,
            4
        );


    fclose(
        $docxHandle
    );


    if (
        $signature === false ||
        strlen($signature) < 2 ||
        substr(
            $signature,
            0,
            2
        ) !== 'PK'
    ) {

        throw new RuntimeException(
            'ملف Word الناتج غير صالح.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DATABASE
    |--------------------------------------------------------------------------
    */

    $stmt =
        $pdo->prepare(
            "
            INSERT INTO conversions
            (
                user_id,
                original_name,
                pdf_path,
                word_path,
                file_size,
                status
            )
            VALUES
            (?, ?, ?, ?, ?, 'completed')
            "
        );


    $stmt->execute(
        [
            get_current_user_id(),

            $originalName,

            $pdfPath,

            $docxPath,

            $fileSize
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    json_response(
        true,
        'تم تحويل ملف PDF إلى Word بنجاح.',
        [

            'file_id' =>
                $fileId,

            'name' =>
                pathinfo(
                    $originalName,
                    PATHINFO_FILENAME
                ) . '.docx',

            'download' =>
                'download.php?file=' .
                urlencode(
                    $docxName
                )
        ]
    );


} catch (
    Throwable $e
) {

    /*
    |--------------------------------------------------------------------------
    | LOG
    |--------------------------------------------------------------------------
    */

    error_log(
        '[PDF_TO_WORD_PYTHON] ' .
        $e->getMessage() .
        PHP_EOL .
        $e->getTraceAsString()
    );


    /*
    |--------------------------------------------------------------------------
    | DELETE FAILED OUTPUT
    |--------------------------------------------------------------------------
    */

    if (
        file_exists($docxPath)
    ) {

        @unlink(
            $docxPath
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE UPLOADED PDF
    |--------------------------------------------------------------------------
    */

    if (
        file_exists($pdfPath)
    ) {

        @unlink(
            $pdfPath
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ERROR RESPONSE
    |--------------------------------------------------------------------------
    */

    json_response(
        false,
        'فشل تحويل ملف PDF: ' .
        $e->getMessage()
    );
}