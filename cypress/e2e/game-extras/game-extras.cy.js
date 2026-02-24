import GameExtrasPage from '../../support/page-objects/GameExtrasPage';

describe('Game Extras', () => {
  const gameExtrasPage = new GameExtrasPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/game-extras/overview*', { fixture: 'game-extras/overview.json' }).as('getGameExtras');
  });

  it('UC88: should list game extras with pagination', () => {
    gameExtrasPage.visit();
    cy.wait('@getGameExtras');

    cy.get('#tbody tr').should('have.length', 3);
    cy.get('#tbody').should('contain.text', 'vs20fruitsw');
    cy.get('#tbody').should('contain.text', 'vs20olympgate');
    cy.get('#tbody').should('contain.text', 'unknown123');
  });

  it('UC89: should search by external_id', () => {
    gameExtrasPage.visit();
    cy.wait('@getGameExtras');

    cy.intercept('GET', '/api/v1/game-extras/overview*q=vs20fruitsw*', {
      body: {
        data: [{ external_id: 'vs20fruitsw', rtp: 96.51, volatility: 'high', min_bet: 0.20, exists_in_base: true, base_name: 'Sweet Bonanza', provider: 'Pragmatic Play' }],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      },
    }).as('searchGameExtras');

    gameExtrasPage.searchByQuery('vs20fruitsw');
    cy.wait('@searchGameExtras');

    cy.get('#tbody tr').should('have.length', 1);
    cy.get('#tbody').should('contain.text', 'vs20fruitsw');
  });

  it('UC90: should sync game extras', () => {
    gameExtrasPage.visit();
    cy.wait('@getGameExtras');

    cy.intercept('POST', '/api/v1/game-extras/sync', {
      statusCode: 200,
      body: { message: 'Game extras sincronizados.' },
    }).as('syncGameExtras');

    cy.intercept('GET', '/api/v1/game-extras/overview*', { fixture: 'game-extras/overview.json' }).as('reloadGameExtras');

    gameExtrasPage.clickSync();

    cy.wait('@syncGameExtras');
    cy.get('.toast').should('be.visible');
  });
});
