<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\Nominee;
use App\Entity\Policy;
use App\Form\QuickPolicyType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QuickPolicyController extends AbstractController
{
    #[Route('/admin/quick-policy', name: 'app_quick_policy')]
    public function index(Request $request, EntityManagerInterface $em, AdminUrlGenerator $adminUrlGenerator): Response
    {
        // Check agency context
        $user = $this->getUser();
        $agency = $user ? $user->getAgency() : null;

        $form = $this->createForm(QuickPolicyType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Server-side duplicate check for policy number
            $existing = $em->getRepository(Policy::class)
                ->findOneBy(['policyNumber' => $data['policyNumber']]);
            if ($existing) {
                $form->get('policyNumber')->addError(
                    new FormError('This Policy Number already exists in the system.')
                );
            }

            // Only persist if no errors were added
            if ($form->isValid() && count($form->get('policyNumber')->getErrors()) === 0) {
                // 1. Create the Client
                $client = new Client();
                $client->setFirstName($data['firstName']);
                $client->setLastName($data['lastName']);
                $client->setMobile($data['mobile']);
                $client->setDob($data['dob']);
                if ($agency) {
                    $client->setAgency($agency);
                }
                $em->persist($client);

                // Create the Policy and link it to the Client
                $policy = new Policy();
                $policy->setClient($client);
                $policy->setPolicyNumber($data['policyNumber']);
                $policy->setLicPlan($data['licPlan']);
                $policy->setCommencementDate($data['commencementDate']);
                $policy->setSumAssured($data['sumAssured']);
                $policy->setPolicyTerm($data['policyTerm']);
                $policy->setPremiumPayingTerm($data['premiumPayingTerm']);
                $policy->setPremiumMode($data['premiumMode']);
                $policy->setBasicPremium($data['basicPremium']);
                $policy->setStatus('IN_FORCE');

                // Set Life Assured DOB from client DOB (triggers entryAge calculation via lifecycle hook)
                if ($data['dob']) {
                    $policy->setLifeAssuredDob($data['dob']);
                }

                // Set LIC Branch
                if (!empty($data['licBranch'])) {
                    $policy->setLicBranch($data['licBranch']);
                }

                if ($agency) {
                    $policy->setAgency($agency);
                }
                
                // The prePersist lifecycle hooks in Policy.php will automatically 
                // calculate the GST, Total Premium, Maturity, and Next Due Dates.
                $em->persist($policy);

                // Create Nominee if nomineeName is provided
                if (!empty($data['nomineeName'])) {
                    $nominee = new Nominee();
                    $nominee->setPolicy($policy);
                    $nominee->setName($data['nomineeName']);
                    $nominee->setRelationship($data['nomineeRelationship'] ?? 'OTHER');
                    $nominee->setSharePercentage((string) ($data['nomineeSharePercentage'] ?? 100));
                    $em->persist($nominee);
                }

                // Save all to database
                $em->flush();

                $this->addFlash('success', 'Client and Policy successfully created together!');

                // Redirect to the Policy List
                $url = $adminUrlGenerator->setController(PolicyCrudController::class)->setAction('index')->generateUrl();
                return $this->redirect($url);
            }
        }

        // Render the form inside the EasyAdmin layout template
        return $this->render('Admin/quick_policy.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * AJAX endpoint: Check if a policy number already exists.
     * Returns JSON { exists: true/false }
     */
    #[Route('/admin/quick-policy/check-policy', name: 'app_quick_policy_check', methods: ['POST'])]
    public function checkPolicy(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $policyNumber = trim($data['policyNumber'] ?? '');

        if ($policyNumber === '') {
            return $this->json(['exists' => false]);
        }

        $existing = $em->getRepository(Policy::class)
            ->findOneBy(['policyNumber' => $policyNumber]);

        return $this->json(['exists' => $existing !== null]);
    }
}