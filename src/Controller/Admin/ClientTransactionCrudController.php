<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\ClientTransaction;
use App\Service\PermissionCheckerService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ClientTransactionCrudController extends BaseCrudController
{
    public function __construct(PermissionCheckerService $permissionChecker)
    {
        parent::__construct($permissionChecker);
    }

    protected function getModuleKey(): string
    {
        return 'client_transactions';
    }

    public static function getEntityFqcn(): string
    {
        return ClientTransaction::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Client Transaction')
            ->setEntityLabelInPlural('Client Transactions')
            ->setDefaultSort(['transactionDate' => 'DESC', 'id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        if ($this->permissionChecker->hasPermission($this->getModuleKey(), 'view')) {
            $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
        }

        // Disable edit/delete to preserve audit integrity — use ADJUSTMENT entries instead
        $actions->disable(Action::EDIT, Action::DELETE);

        return $actions;
    }

    public function createEntity(string $entityFqcn)
    {
        $txn = new ClientTransaction();
        $user = $this->getUser();

        if ($user && $user->getAgency()) {
            $txn->setAgency($user->getAgency());
        }

        $txn->setTransactionDate(new \DateTime());

        return $txn;
    }

    public function configureFields(string $pageName): iterable
    {
        // LEFT COLUMN
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Transaction Details')
            ->setIcon('fa fa-exchange-alt')
            ->setHelp('Record a money movement between agency and client');

        yield AssociationField::new('client', 'Client')
            ->setRequired(true)
            ->setColumns(12);

        yield ChoiceField::new('type', 'Transaction Type')
            ->setChoices(ClientTransaction::TYPES)
            ->renderAsBadges([
                ClientTransaction::TYPE_COLLECTION => 'success',
                ClientTransaction::TYPE_SETTLEMENT => 'info',
                ClientTransaction::TYPE_ADJUSTMENT => 'warning',
            ])
            ->setRequired(true)
            ->setColumns(12);

        // On detail/index, show all types including auto-generated PAID_TO_LIC
        yield TextField::new('type', 'Type')
            ->onlyOnIndex()
            ->formatValue(static function (?string $value): string {
                return match ($value) {
                    ClientTransaction::TYPE_COLLECTION  => '⬇️ Collection',
                    ClientTransaction::TYPE_PAID_TO_LIC => '⬆️ Paid to LIC',
                    ClientTransaction::TYPE_SETTLEMENT  => '🤝 Settlement',
                    ClientTransaction::TYPE_ADJUSTMENT  => '🔧 Adjustment',
                    default => $value ?? '',
                };
            });

        yield DateField::new('transactionDate', 'Date')
            ->setColumns(12);

        // RIGHT COLUMN
        yield FormField::addColumn(6);

        yield FormField::addFieldset('Amount & Notes')->setIcon('fa fa-money-bill-wave');

        yield MoneyField::new('amount', 'Amount')
            ->setCurrency('INR')
            ->setStoredAsCents(false)
            ->setRequired(true)
            ->setColumns(12);

        yield TextareaField::new('description', 'Description / Notes')
            ->setColumns(12)
            ->hideOnIndex();

        // Read-only linked receipt (shown in detail/index)
        yield AssociationField::new('premiumReceipt', 'Linked Receipt')
            ->hideOnForm()
            ->setColumns(12);

        // META DATA
        yield FormField::addFieldset('System Metadata')->setIcon('fa fa-database');

        $agencyField = AssociationField::new('agency', 'Agency')
            ->setColumns(12);

        $user = $this->getUser();
        if ($user->isAdministrator()) {
            yield $agencyField
                ->setRequired(true)
                ->setHelp('Super Admin Only: Assign transaction to a specific agency');
        } else {
            yield $agencyField
                ->hideOnIndex()
                ->setDisabled(true);
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ClientTransaction) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        $user = $this->getUser();

        // Agency fallback
        if ($user && $user->getAgency() && !$entityInstance->getAgency()) {
            $entityInstance->setAgency($user->getAgency());
        }

        parent::persistEntity($entityManager, $entityInstance);
    }
}
