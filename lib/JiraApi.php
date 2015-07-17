<?php
class JiraApi extends CompanyInfrastructure\JiraApi
{
    /**
     * Метод двигает задачу по статусам, на основании наших транзишенов
     *
     * @param array $ticketInfo - информация о тикете, возвращаемая методом getTicketInfo()
     * @param string $transition - элемент из констант класса Jira\Transition, например Jira\Transition::START_PROGRESS
     * @param null $comment
     * @param bool $ignoreIncorrectStatus
     * @throws ApplicationException
     */
    public function transitionTicket($ticketInfo, $transition, $comment = null, $ignoreIncorrectStatus = false)
    {
        if (!isset(Jira\Transition::$transitionMap[$transition])) {
            throw new ApplicationException("Unknown tramsition '$transition'");
        }

        list($from, $to) = Jira\Transition::$transitionMap[$transition];

        if ($ticketInfo["fields"]["status"]["name"] != $from) {
            $this->debugLogger->message("Ignore ticket status: $ignoreIncorrectStatus");
            if ($ignoreIncorrectStatus) {
                $this->debugLogger->error("Can't apply transition '$transition', because ticket is in {$ticketInfo["fields"]["status"]["name"]} status, but $from needed, skip exception");
                return;
            }
            throw new ApplicationException("Can't apply transition '$transition', because ticket is in {$ticketInfo["fields"]["status"]["name"]} status, but $from needed");
        }

        $transitionId = null;
        $availableStatuses = [];
        foreach ($ticketInfo['transitions'] as $transitionItem) {
            $availableStatuses[] = $transitionItem['to']['name'];
            if ($transitionItem['to']['name'] == $to) {
                $transitionId = $transitionItem['id'];
            }
        }

        if (empty($transitionId)) {
            throw new ApplicationException("Can't apply transition $transition to ticket {$ticketInfo['key']}, despite the fact status of ticket=$from and is correct. Check JIRA roles for this project. Available statuses: ".implode(", ", $availableStatuses));
        }

        $this->updateTicketTransition($ticketInfo['key'], $transitionId, $comment);
    }
}
