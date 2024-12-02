<?php
declare(strict_types=1);

namespace App\Command\Salesforce;

use App\Entity\Magento\Customer;
use App\Entity\Main\SalesforceCustomerLead;
use App\Repository\Main\SalesforceCustomerLeadRepository;
use App\Service\Salesforce\Common\ApiTokenService;
use App\Service\Salesforce\Customer\LeadServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "salesforce:lead:create",
    description: "Create lead",
    hidden: false
)]
class LeadCreateCommand extends Command
{
    private string $token;
    private string $url;

    private EntityRepository $mCustomerRepository;

    public function __construct(
        private readonly EntityManagerInterface $magentoEntityManager,
        private readonly SalesforceCustomerLeadRepository $salesforceCustomerLeadRepository,
        private readonly ApiTokenService      $apiTokenService,
        private readonly LeadServiceInterface $leadService,
        string                                $name = null
    ) {
        $this->mCustomerRepository = $this->magentoEntityManager->getRepository(Customer::class);

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of customer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->mCustomerRepository->getLeads();
        //$output->writeln('Array: ' . var_dump($result));

        foreach ($result as $mCustomer) {
            //$output->writeln('Id: ' . $mCustomer->getId() . ', Email: ' . $mCustomer->getEmail() . ', Id: ' . $mCustomer->getId());

            $lead = $this->salesforceCustomerLeadRepository->findOneBy(['customerId' => $mCustomer->getId()]);
            if ($lead === null) {
                $lead = new SalesforceCustomerLead();
                $lead->setStatus('NEW');
            }
            $lead
                ->setEmail($mCustomer->getEmail())
                ->setWebsiteId($mCustomer->getWebsiteId())
                ->setCustomerId($mCustomer->getId())
                ->setFirstName($mCustomer->getFirstName())
                ->setLastName($mCustomer->getLastName());

            $this->salesforceCustomerLeadRepository->add($lead);
        }

        return Command::SUCCESS;
    }

    private function createCustomer(InputInterface $input, OutputInterface $output): void
    {
        $email = $input->getArgument('email');

        $this->getToken();

        //$token = $this->apiTokenService->getToken();
        //$url = $this->apiTokenService->getInstanceUrl();
        //$output->writeln('Token: ' . $token);
        //$output->writeln('Url: ' . $url);

        $result = $this->leadService->createCustomer($email, $this->url, $this->token);
        if (!$result) {
            $output->writeln('Something went wrong!');
        }
        $output->writeln('Array: ' . var_dump($result));

        $output->writeln('The customer created!');
    }

    private function getToken(): void
    {
        $this->token = $this->apiTokenService->getToken();
        $this->url = $this->apiTokenService->getInstanceUrl();
    }
}
