<?php

// Interface untuk handler dalam Chain of Responsibility
interface SupportHandler {
    public function setNext(SupportHandler $handler);
    public function handleRequest(Request $request);
}

// Implementasi dari interface SupportHandler
abstract class AbstractSupportHandler implements SupportHandler {
    private $nextHandler;

    public function setNext(SupportHandler $handler) {
        $this->nextHandler = $handler;
    }

    public function handleRequest(Request $request) {
        if ($this->nextHandler !== null) {
            $this->nextHandler->handleRequest($request);
        } else {
            echo "Permintaan tidak dapat diproses.\n";
        }
    }
}

// Objek untuk mewakili permintaan dari pelanggan
class Request {
    private $type;
    private $content;

    public function __construct(string $type, string $content) {
        $this->type = $type;
        $this->content = $content;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getContent(): string {
        return $this->content;
    }
}

// Concrete handler pertama untuk menangani permintaan umum
class GeneralSupportHandler extends AbstractSupportHandler {
    public function handleRequest(Request $request) {
        if ($request->getType() === "general") {
            echo "GeneralSupportHandler: Menangani permintaan umum: {$request->getContent()}\n";
        } else {
            parent::handleRequest($request);
        }
    }
}

// Concrete handler kedua untuk menangani permintaan teknis
class TechnicalSupportHandler extends AbstractSupportHandler {
    public function handleRequest(Request $request) {
        if ($request->getType() === "technical") {
            echo "TechnicalSupportHandler: Menangani permintaan teknis: {$request->getContent()}\n";
        } else {
            parent::handleRequest($request);
        }
    }
}

// Concrete handler ketiga untuk menangani keluhan pelanggan
class ComplaintsHandler extends AbstractSupportHandler {
    public function handleRequest(Request $request) {
        if ($request->getType() === "complaint") {
            echo "ComplaintsHandler: Menangani keluhan: {$request->getContent()}\n";
        } else {
            parent::handleRequest($request);
        }
    }
}

// Concrete handler terakhir untuk menangani permintaan eskalasi
class EscalationHandler extends AbstractSupportHandler {
    public function handleRequest(Request $request) {
        echo "Eskalasi permintaan: {$request->getContent()}\n";
    }
}

// Penggunaan Chain of Responsibility untuk menangani permintaan customer service
$generalHandler = new GeneralSupportHandler();
$technicalHandler = new TechnicalSupportHandler();
$complaintsHandler = new ComplaintsHandler();
$escalationHandler = new EscalationHandler();

$generalHandler->setNext($technicalHandler);
$technicalHandler->setNext($complaintsHandler);
$complaintsHandler->setNext($escalationHandler);

// Pengajuan permintaan customer service
$request1 = new Request("general", "Permintaan informasi produk baru");
$request2 = new Request("technical", "Masalah koneksi internet");
$request3 = new Request("complaint", "Pelayanan tidak memuaskan");

// Memproses permintaan customer service
echo "Memproses permintaan 1:\n";
$generalHandler->handleRequest($request1);
echo "\n";

echo "Memproses permintaan 2:\n";
$generalHandler->handleRequest($request2);
echo "\n";

echo "Memproses permintaan 3:\n";
$generalHandler->handleRequest($request3);
echo "\n";
