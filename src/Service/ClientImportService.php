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
            // 0: First Name, 1: Last Name, 2: DOB, 3: Mobile
            // 4: Email, 5: Address, 6: City, 7: Pincode

            $firstName = $row[0] ?? null;
            $mobile = $row[3] ?? null;

            if (!$firstName || !$mobile) {
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
            $client->setFirstName($firstName);
            $client->setLastName($row[1] ?? '');
            
            // Handle Date
            if (!empty($row[2])) {
                try {
                    $client->setDob(new \DateTime($row[2]));
                } catch (\Exception $e) {
                    $client->setDob(new \DateTime('1990-01-01')); // Default fallback
                }
            } else {
                $client->setDob(new \DateTime('1990-01-01'));
            }

            $client->setMobile($mobile);
            $client->setEmail($row[4] ?? null);
            $client->setAddress($row[5] ?? null);
            $client->setCity($row[6] ?? null);
            $client->setPincode($row[7] ?? null);
            $client->setAgency($agency);

            $this->em->persist($client);
            $count++;
        }

        $this->em->flush();
        return $count;
    }
}