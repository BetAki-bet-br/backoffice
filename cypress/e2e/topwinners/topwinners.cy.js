import TopWinnersPage from '../../support/page-objects/TopWinnersPage';

describe('Top Winners', () => {
  const topWinnersPage = new TopWinnersPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/winners/batches*', { fixture: 'topwinners/list.json' }).as('getTopWinners');
  });

  it('UC70: should list top winners', () => {
    topWinnersPage.visit();
    cy.wait('@getTopWinners');

    cy.get('#tbody tr').should('have.length', 2);
    cy.get('#tbody').should('contain.text', 'Vencedores Janeiro');
    cy.get('#tbody').should('contain.text', 'Vencedores Fevereiro');
  });

  it('UC71: should filter by vertical and status', () => {
    topWinnersPage.visit();
    cy.wait('@getTopWinners');

    cy.intercept('GET', '/api/v1/winners/batches*vertical=slots*', {
      body: {
        data: [{ id: 1, title: 'Vencedores Janeiro', vertical: 'slots', status: 'published', period_start: '2025-01-01', period_end: '2025-01-31' }],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('getTopWinnersSlots');

    cy.get('#vertical').select('slots');
    cy.get('#btnSearch').click();
    cy.wait('@getTopWinnersSlots');

    cy.get('#tbody tr').should('have.length', 1);
  });

  it('UC72: should create a winner batch', () => {
    topWinnersPage.visit();
    cy.wait('@getTopWinners');

    cy.intercept('POST', '/api/v1/winners/batches', {
      statusCode: 201,
      body: { data: { id: 3, title: 'Vencedores Março', vertical: 'slots', status: 'draft' } },
    }).as('createWinners');

    cy.intercept('GET', '/api/v1/winners/batches*', { fixture: 'topwinners/list.json' }).as('reloadTopWinners');

    topWinnersPage.clickNew();
    cy.get('#editModal').should('be.visible');

    topWinnersPage.fillTitle('Vencedores Março');
    topWinnersPage.fillPeriodStart('2025-03-01');
    topWinnersPage.fillPeriodEnd('2025-03-31');
    topWinnersPage.clickSave();

    cy.wait('@createWinners');
    cy.get('.toast').should('contain.text', 'criado');
  });

  it('UC73: should edit a winner batch', () => {
    topWinnersPage.visit();
    cy.wait('@getTopWinners');

    cy.intercept('PUT', '/api/v1/winners/batches/1', {
      statusCode: 200,
      body: { data: { id: 1, title: 'Vencedores Janeiro Updated', status: 'published' } },
    }).as('updateWinners');

    cy.intercept('GET', '/api/v1/winners/batches*', { fixture: 'topwinners/list.json' }).as('reloadTopWinners');

    topWinnersPage.clickEditOnRow(1);
    cy.get('#editModal').should('be.visible');

    cy.get('#f_title').clear().type('Vencedores Janeiro Updated');
    topWinnersPage.clickSave();

    cy.wait('@updateWinners');
    cy.get('.toast').should('contain.text', 'atualizado');
  });

  it('UC74: should manage winners data', () => {
    topWinnersPage.visit();
    cy.wait('@getTopWinners');

    cy.intercept('GET', '/api/v1/winners/batches/1', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          title: 'Vencedores Janeiro',
          results: [{ player_name: 'Player1', prize: 10000, position: 1 }],
        },
      },
    }).as('getWinnersResults');

    cy.intercept('PUT', '/api/v1/winners/batches/1/results', {
      statusCode: 200,
      body: { message: 'Resultados sincronizados.' },
    }).as('syncWinnersResults');

    topWinnersPage.clickWinnersOnRow(1);
    cy.get('.modal').should('be.visible');
  });

  it('UC75: should publish and archive a batch', () => {
    topWinnersPage.visit();
    cy.wait('@getTopWinners');

    // Publish
    cy.intercept('POST', '/api/v1/winners/batches/2/publish', {
      statusCode: 200,
      body: { data: { id: 2, status: 'published' } },
    }).as('publishWinners');

    cy.intercept('GET', '/api/v1/winners/batches*', { fixture: 'topwinners/list.json' }).as('reloadTopWinners');

    topWinnersPage.clickPublishOnRow(2);
    cy.wait('@publishWinners');
    cy.get('.toast').should('be.visible');

    // Archive
    cy.intercept('POST', '/api/v1/winners/batches/1/archive', {
      statusCode: 200,
      body: { data: { id: 1, status: 'archived' } },
    }).as('archiveWinners');

    topWinnersPage.clickArchiveOnRow(1);
    cy.wait('@archiveWinners');
    cy.get('.toast').should('be.visible');
  });
});
