<?php

namespace RefactoringGuru\ChainOfResponsibility\RealWorld;

/**
 * Pola CoR klasik mendefinisikan satu peran untuk objek yang membentuk rantai,
 * yaitu Handler. Dalam contoh ini, kita membedakan antara middleware dan
 * handler aplikasi akhir, yang dieksekusi ketika permintaan melewati semua
 * objek middleware.
 *
 * Kelas Middleware dasar mendeklarasikan antarmuka untuk menghubungkan objek
 * middleware menjadi rantai.
 */
abstract class Middleware
{
    /**
     * @var Middleware
     */
    private $next;

    /**
     * Metode ini digunakan untuk membangun rantai objek middleware.
     */
    public function linkWith(Middleware $next): Middleware
    {
        $this->next = $next;

        return $next;
    }

    /**
     * Subkelas harus mengganti metode ini untuk menyediakan pemeriksaan mereka
     * sendiri. Subkelas dapat menggunakan implementasi induk jika tidak dapat
     * memproses permintaan.
     */
    public function check(string $email, string $password): bool
    {
        if (!$this->next) {
            return true;
        }

        return $this->next->check($email, $password);
    }
}

/**
 * Middleware Konkrit ini memeriksa apakah pengguna dengan kredensial yang
 * diberikan ada.
 */
class UserExistsMiddleware extends Middleware
{
    private $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function check(string $email, string $password): bool
    {
        if (!$this->server->hasEmail($email)) {
            echo "UserExistsMiddleware: Email ini tidak terdaftar!\n";

            return false;
        }

        if (!$this->server->isValidPassword($email, $password)) {
            echo "UserExistsMiddleware: Password salah!\n";

            return false;
        }

        return parent::check($email, $password);
    }
}

/**
 * Middleware Konkrit ini memeriksa apakah pengguna yang terkait dengan permintaan
 * memiliki izin yang cukup.
 */
class RoleCheckMiddleware extends Middleware
{
    public function check(string $email, string $password): bool
    {
        if ($email === "admin@example.com") {
            echo "RoleCheckMiddleware: Halo, admin!\n";

            return true;
        }
        echo "RoleCheckMiddleware: Halo, pengguna!\n";

        return parent::check($email, $password);
    }
}

/**
 * Middleware Konkrit ini memeriksa apakah terlalu banyak permintaan login yang
 * gagal.
 */
class ThrottlingMiddleware extends Middleware
{
    private $requestPerMinute;
    private $request;
    private $currentTime;

    public function __construct(int $requestPerMinute)
    {
        $this->requestPerMinute = $requestPerMinute;
        $this->currentTime = time();
    }

    /**
     * Perhatikan bahwa panggilan parent::check dapat dimasukkan baik di
     * awal maupun akhir metode ini.
     *
     * Hal ini memberikan lebih banyak fleksibilitas daripada loop sederhana
     * atas semua objek middleware. Sebagai contoh, sebuah middleware dapat
     * mengubah urutan pemeriksaan dengan menjalankan pemeriksaannya setelah
     * semua yang lain.
     */
    public function check(string $email, string $password): bool
    {
        if (time() > $this->currentTime + 60) {
            $this->request = 0;
            $this->currentTime = time();
        }

        $this->request++;

        if ($this->request > $this->requestPerMinute) {
            echo "ThrottlingMiddleware: Batas permintaan terlampaui!\n";
            die();
        }

        return parent::check($email, $password);
    }
}

/**
 * Ini adalah kelas aplikasi yang bertindak sebagai handler akhir nyata. Kelas
 * Server menggunakan pola CoR untuk menjalankan serangkaian middleware
 * autentikasi sebelum meluncurkan beberapa logika bisnis terkait permintaan.
 */
class Server
{
    private $users = [];

    /**
     * @var Middleware
     */
    private $middleware;

    /**
     * Klien dapat mengonfigurasi server dengan rantai objek middleware.
     */
    public function setMiddleware(Middleware $middleware): void
    {
        $this->middleware = $middleware;
    }

    /**
     * Server mendapatkan email dan password dari klien dan mengirimkan
     * permintaan otorisasi ke middleware.
     */
    public function logIn(string $email, string $password): bool
    {
        if ($this->middleware->check($email, $password)) {
            echo "Server: Otorisasi berhasil!\n";

            // Lakukan sesuatu yang berguna untuk pengguna yang diotorisasi.

            return true;
        }

        return false;
    }

    public function register(string $email, string $password): void
    {
        $this->users[$email] = $password;
    }

    public function hasEmail(string $email): bool
    {
        return isset($this->users[$email]);
    }

    public function isValidPassword(string $email, string $password): bool
    {
        return $this->users[$email] === $password;
    }
}

/**
 * Kode klien.
 */
$server = new Server();
$server->register("admin@example.com", "admin_pass");
$server->register("user@example.com", "user_pass");

// Semua middleware terhubung. Klien dapat membuat berbagai konfigurasi
// rantai tergantung pada kebutuhannya.
$middleware = new ThrottlingMiddleware(2);
$middleware
    ->linkWith(new UserExistsMiddleware($server))
    ->linkWith(new RoleCheckMiddleware());

// Server mendapatkan rantai dari kode klien.
$server->setMiddleware($middleware);

// ...

do {
    echo "\nMasukkan email Anda:\n";
    $email = readline();
    echo "Masukkan password Anda:\n";
    $password = readline();
    $success = $server->logIn($email, $password);
} while (!$success);
