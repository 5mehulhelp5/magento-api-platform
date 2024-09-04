<?php
declare(strict_types=1);

namespace App\Command;

use App\Email\EmailSerializer;
use App\Email\Newsletter\SubscribeConfirm;
use App\Message\ExternalEmail;
use Symfony\Component\Serializer\SerializerInterface;
use App\Service\BloomreachMailer\SenderServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: "app:send:email",
    description: "Send email via service",
    hidden: false
)]
class SendEmailCommand extends Command
{
    public function __construct(
        private readonly SenderServiceInterface $senderService,
        private readonly SerializerInterface $serializer,
        private readonly MessageBusInterface $messageBus,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of customer')
            ->addArgument('queue', InputArgument::OPTIONAL, 'Send via queue', true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');

        $subscribeConfirm = new SubscribeConfirm();
        $subscribeConfirm
            ->setEmail($email)
            ->setConfirmCode('1')
            ->setWebsiteId(1)
            ->setStoreId(1);

        if ($input->getArgument('queue')) {
            $properties = ['header' => 'email', 'type' => $subscribeConfirm->getEmailType()];
            $body = $this->serializer->normalize($subscribeConfirm, null, ['groups' => 'body']);

            $this->messageBus->dispatch(
                message: new ExternalEmail($properties, $body)
            );
        } else {
            $this->senderService->sendEmail($subscribeConfirm);
        }

        $output->writeln('The email was sent!');
        return Command::SUCCESS;
    }
}
