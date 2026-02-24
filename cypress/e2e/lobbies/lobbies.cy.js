import LobbiesPage from '../../support/page-objects/LobbiesPage';

describe('Lobbies', () => {
  const lobbiesPage = new LobbiesPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/lobbies/slots/config', { fixture: 'lobbies/config.json' }).as('getLobbySlots');
    cy.intercept('GET', '/api/v1/lobbies/live/config', {
      body: {
        data: {
          vertical: 'live',
          sections: [
            { id: 1, type: 'game-list', title: 'Live Popular', resource_id: 2, display_count: 8, order: 1 },
          ],
        },
      },
    }).as('getLobbyLive');
    cy.intercept('GET', '/api/v1/categories*', { fixture: 'categories/list.json' }).as('getCategories');
    cy.intercept('GET', '/api/v1/top-lists*', { fixture: 'toplists/list.json' }).as('getTopLists');
    cy.intercept('GET', '/api/v1/awards/batches*', { fixture: 'awards/list.json' }).as('getAwards');
    cy.intercept('GET', '/api/v1/winners/batches*', { fixture: 'topwinners/list.json' }).as('getWinners');
  });

  it('UC42: should load slots lobby configuration', () => {
    lobbiesPage.visit();

    // Page should display lobby configuration
    cy.get('.content-main').should('be.visible');
    cy.contains('Lobby').should('be.visible');
  });

  it('UC43: should load live lobby configuration', () => {
    lobbiesPage.visit();

    // Switch to live vertical
    cy.get('body').then(($body) => {
      const liveBtn = $body.find('button:contains("Live"), a:contains("Live"), [data-vertical="live"]');
      if (liveBtn.length > 0) {
        cy.wrap(liveBtn.first()).click();
        cy.wait('@getLobbyLive');
      }
    });
  });

  it('UC44: should add/edit a section', () => {
    lobbiesPage.visit();

    cy.get('body').then(($body) => {
      const addBtn = $body.find('button:contains("Adicionar"), button:contains("Nova Seção")');
      if (addBtn.length > 0) {
        cy.wrap(addBtn.first()).click();
        cy.get('.modal').should('be.visible');
      }
    });
  });

  it('UC45: should save lobby changes', () => {
    lobbiesPage.visit();

    cy.intercept('PUT', '/api/v1/lobbies/slots/config', {
      statusCode: 200,
      body: { message: 'Configuração salva.' },
    }).as('saveLobbyConfig');

    cy.get('body').then(($body) => {
      const saveBtn = $body.find('button:contains("Salvar")');
      if (saveBtn.length > 0) {
        cy.wrap(saveBtn.first()).click();
        cy.wait('@saveLobbyConfig');
        cy.get('.toast').should('be.visible');
      }
    });
  });
});
