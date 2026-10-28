<?php

declare(strict_types=1);

namespace Tests\View;

use AEFS\Core\View\ViewEngineInterface;
use App\Models\User;
use RuntimeException;

final readonly class ViewEngineSmokeTest
{
    public function __construct(
        private ViewEngineInterface $view
    ) {
    }

    public function run(): void
    {
        $this->assertViewExists('layouts.base');
        $this->assertViewExists('layouts.app');
        $this->assertViewExists('layouts.guest');

        $this->assertViewExists('partials.header');
        $this->assertViewExists('partials.sidebar');
        $this->assertViewExists('partials.flash');
        $this->assertViewExists('partials.errors');

        $this->assertViewExists('components.card');
        $this->assertViewExists('components.button');
        $this->assertViewExists('components.link-button');
        $this->assertViewExists('components.alert');

        $this->assertViewExists('auth.login');
        $this->assertViewExists('dashboard.index');

        $html = $this->view->render(
            'dashboard.index',
            [
                'isAdmin' => true,
                'statistics' => [
                    'members' => 12,
                    'pending' => 3,
                    'eventRegistrations' => 7,
                    'users' => 9,
                    'eventCancellations' => 2,
                    'events' => 4,
                ],
                'latestMembers' => [
                    [
                        'voornaam' => 'Test',
                        'achternaam' => 'Gebruiker',
                        'gemeente' => 'Mechelen',
                    ],
                ],
                'upcomingEvents' => [
                    [
                        'titel' => 'Testevenement',
                        'startdatum' => '2026-08-01',
                    ],
                ],
                'openShifts' => [],
            ]
        );

        $this->assertContains(
            '<!DOCTYPE html>',
            $html
        );

        $this->assertContains(
            'Dashboard',
            $html
        );

        $this->assertContains(
            '12',
            $html
        );

        $this->assertContains(
            '9',
            $html
        );

        $this->assertContains(
            '4',
            $html
        );

        $this->assertContains(
            'Test Gebruiker',
            $html
        );

        $this->assertContains(
            'Testevenement',
            $html
        );

        $this->assertNotContains(
            'Wachtende eventinschrijvingen',
            $html
        );

        $this->assertNotContains(
            'Openstaande annulatieaanvragen',
            $html
        );

        $this->assertNotContains(
            '<h3 class="dashboard-section__title">' . PHP_EOL
                . '                        Wachtende accounts',
            $html
        );

        $this->assertContains(
            'sidebar',
            $html
        );

        $this->assertContains(
            'app__content',
            $html
        );

        $this->assertUsersView();
        $this->assertMailingComposeView();
    }


    private function assertUsersView(): void
    {
        $user = new User(
            1,
            1,
            'test@example.com',
            User::ROLE_ADMIN,
            true,
            false,
            '',
            null,
            null,
            'Test',
            'Gebruiker'
        );

        $html = $this->view->render(
            'users.index',
            [
                'title' => 'Gebruikers',
                'zoekterm' => '',
                'gebruikers' => [$user],
            ]
        );

        $this->assertContains(
            'Alle gebruikers',
            $html
        );

        $this->assertContains(
            'Gebruiker zoeken',
            $html
        );

        $this->assertContains(
            'Wijzigen',
            $html
        );

        if (substr_count($html, 'card__title') !== 1) {
            throw new RuntimeException(
                'Componentdata lekt nog naar de gebruikerskaarten.'
            );
        }
    }

    private function assertMailingComposeView(): void
    {
        $html = $this->view->render(
            'mailings.create',
            [
                'title' => 'Nieuwe mailing',
                'options' => [
                    'events' => [],
                    'shifts' => [],
                ],
                'mailConfigured' => true,
                'recipientRestriction' => [
                    'active' => false,
                    'emails' => [],
                ],
            ]
        );

        $this->assertContains('Nieuwsbrief', $html);
        $this->assertContains('name="bericht_type"', $html);
        $this->assertContains('value="nieuwsbrief"', $html);
    }

    private function assertViewExists(string $view): void
    {
        if ($this->view->exists($view)) {
            return;
        }

        throw new RuntimeException(
            sprintf(
                'Verplichte view [%s] bestaat niet.',
                $view
            )
        );
    }

    private function assertContains(
        string $expected,
        string $actual
    ): void {
        if (str_contains($actual, $expected)) {
            return;
        }

        throw new RuntimeException(
            sprintf(
                'Verwachte inhoud [%s] werd niet gerenderd.',
                $expected
            )
        );
    }

    private function assertNotContains(
        string $unexpected,
        string $actual
    ): void {
        if (!str_contains($actual, $unexpected)) {
            return;
        }

        throw new RuntimeException(
            sprintf(
                'Onverwachte inhoud [%s] werd gerenderd.',
                $unexpected
            )
        );
    }
}
