import ProvidersPage from '../../support/page-objects/ProvidersPage';

describe('Providers', () => {
  const providersPage = new ProvidersPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/providers*', { fixture: 'providers/list.json' }).as('getProviders');
  });

  it('UC24: should list providers with filters', () => {
    providersPage.visit();
    cy.wait('@getProviders');

    cy.get('#tbody tr').should('have.length', 3);
    cy.get('#tbody').should('contain.text', 'Pragmatic Play');
    cy.get('#tbody').should('contain.text', 'Evolution Gaming');
    cy.get('#tbody').should('contain.text', 'PG Soft');
  });

  it('UC25: should filter by vertical and status', () => {
    providersPage.visit();
    cy.wait('@getProviders');

    cy.intercept('GET', '/api/v1/providers*vertical=slots*', {
      body: {
        data: [
          { id: 1, external_id: 'pragmatic', name: 'Pragmatic Play', games_count: 150, verticals: ['slots'], status: 'active' },
          { id: 3, external_id: 'pgsoft', name: 'PG Soft', games_count: 45, verticals: ['slots'], status: 'inactive' },
        ],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('getProvidersByVertical');

    cy.get('#vertical').select('slots');
    cy.get('#btnSearch').click();
    cy.wait('@getProvidersByVertical');

    cy.get('#tbody tr').should('have.length', 2);
  });

  it('UC26: should edit a provider (name, status, verticals)', () => {
    providersPage.visit();
    cy.wait('@getProviders');

    cy.intercept('PUT', '/api/v1/providers/1', {
      statusCode: 200,
      body: { data: { id: 1, name: 'Pragmatic Play Updated', status: 'active' } },
    }).as('updateProvider');

    cy.intercept('GET', '/api/v1/providers*', { fixture: 'providers/list.json' }).as('reloadProviders');

    providersPage.clickEditOnRow(1);
    cy.get('#editModal').should('be.visible');

    cy.get('#f_name').clear().type('Pragmatic Play Updated');
    providersPage.clickSave();

    cy.wait('@updateProvider');
    cy.get('.toast').should('be.visible');
  });

  it('UC27: should view games of a provider', () => {
    providersPage.visit();
    cy.wait('@getProviders');

    cy.intercept('GET', '/api/v1/slots*', {
      body: {
        data: [
          { id: 1, title: 'Sweet Bonanza', provider_game_id: 'vs20fruitsw', status: 'active' },
        ],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('getProviderGames');

    providersPage.clickViewGamesOnRow(1);
    cy.get('.modal').should('be.visible');
  });

  it('UC28b: should deactivate all slots of a provider', () => {
    providersPage.visit();
    cy.wait('@getProviders');

    cy.intercept('GET', '/api/v1/providers/1', {
      statusCode: 200,
      body: { id: 1, external_id: 'pragmatic', name: 'Pragmatic Play', games_count: 150, verticals: ['slots'], status: 'active' },
    }).as('getProvider');

    cy.intercept('POST', '/api/v1/providers/1/deactivate-slots', {
      statusCode: 200,
      body: { affected: 5 },
    }).as('deactivateSlots');

    providersPage.clickEditOnRow(1);
    cy.wait('@getProvider');
    cy.get('#editModal').should('be.visible');

    cy.on('window:confirm', () => true);

    providersPage.clickDeactivateSlots();

    cy.wait('@deactivateSlots');
    cy.get('.toast').should('contain.text', 'desativado');
  });

  it('UC28c: should activate all slots of a provider', () => {
    providersPage.visit();
    cy.wait('@getProviders');

    cy.intercept('GET', '/api/v1/providers/3', {
      statusCode: 200,
      body: { id: 3, external_id: 'pgsoft', name: 'PG Soft', games_count: 45, verticals: ['slots'], status: 'inactive' },
    }).as('getProvider');

    cy.intercept('POST', '/api/v1/providers/3/activate-slots', {
      statusCode: 200,
      body: { affected: 12 },
    }).as('activateSlots');

    providersPage.clickEditOnRow(3);
    cy.wait('@getProvider');
    cy.get('#editModal').should('be.visible');

    cy.on('window:confirm', () => true);

    providersPage.clickActivateSlots();

    cy.wait('@activateSlots');
    cy.get('.toast').should('contain.text', 'reativado');
  });

  it('UC28: should sync providers', () => {
    providersPage.visit();
    cy.wait('@getProviders');

    cy.intercept('POST', '/api/v1/providers/sync', {
      statusCode: 200,
      body: { message: 'Providers sincronizados.' },
    }).as('syncProviders');

    cy.intercept('GET', '/api/v1/providers*', { fixture: 'providers/list.json' }).as('reloadProviders');

    providersPage.clickSync();

    cy.wait('@syncProviders');
    cy.get('.toast').should('be.visible');
  });
});
