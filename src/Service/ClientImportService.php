<?php

namespace App\Service;

use App\Entity\Agency;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ClientImportService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function importClients(UploadedFile $file, Agency $agency): int
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $count = 0;
        
        // Assume Row 1 is Headers, so start from Row 2 (Index 1)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Map Columns:
            // 0: Name, 1: DOB, 2: Mobile
            // 3: Email, 4: Address, 5: City, 6: Pincode

            $name = $row[0] ?? null;
            $mobile = $row[2] ?? null;

            if (!$name || !$mobile) {
                continue; // Skip invalid rows
            }

            // Check duplicate (Mobile + Agency)
            $existing = $this->em->getRepository(Client::class)->findOneBy([
                'mobile' => $mobile,
                'agency' => $agency
            ]);

            if ($existing) {
                continue; // Skip if already exists
            }

            $client = new Client();
            $client->setName($name);
            
            // Handle Date
            if (!empty($row[1])) {
                try {
                    $client->setDob(new \DateTime($row[1]));
                } catch (\Exception $e) {
                    $client->setDob(new \DateTime('1990-01-01')); // Default fallback
                }
            } else {
                $client->setDob(new \DateTime('1990-01-01'));
            }

            $client->setMobile($mobile);
            $client->setEmail($row[3] ?? null);
            $client->setAddress($row[4] ?? null);
            $client->setCity($row[5] ?? null);
            $client->setPincode($row[6] ?? null);
            $client->setAgency($agency);

            $this->em->persist($client);
            $count++;
        }

        $this->em->flush();
        return $count;
    }
}