import AwardsPage from '../../support/page-objects/AwardsPage';

describe('Awards (Jogos Premiados)', () => {
  const awardsPage = new AwardsPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/awards/batches*', { fixture: 'awards/list.json' }).as('getAwards');
  });

  it('UC64: should list awards', () => {
    awardsPage.visit();
    cy.wait('@getAwards');

    cy.get('#tbody tr').should('have.length', 2);
    cy.get('#tbody').should('contain.text', 'Premiados Janeiro');
    cy.get('#tbody').should('contain.text', 'Premiados Fevereiro');
  });

  it('UC65: should filter by vertical and status', () => {
    awardsPage.visit();
    cy.wait('@getAwards');

    cy.intercept('GET', '/api/v1/awards/batches*vertical=slots*', {
      body: {
        data: [{ id: 1, title: 'Premiados Janeiro', vertical: 'slots', status: 'published', period_start: '2025-01-01', period_end: '2025-01-31' }],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('getAwardsSlots');

    cy.get('#vertical').select('slots');
    cy.get('#btnSearch').click();
    cy.wait('@getAwardsSlots');

    cy.get('#tbody tr').should('have.length', 1);
  });

  it('UC66: should create an award batch', () => {
    awardsPage.visit();
    cy.wait('@getAwards');

    cy.intercept('POST', '/api/v1/awards/batches', {
      statusCode: 201,
      body: { data: { id: 3, title: 'Premiados Março', vertical: 'slots', status: 'draft' } },
    }).as('createAward');

    cy.intercept('GET', '/api/v1/awards/batches*', { fixture: 'awards/list.json' }).as('reloadAwards');

    awardsPage.clickNew();
    cy.get('#editModal').should('be.visible');

    awardsPage.fillTitle('Premiados Março');
    awardsPage.fillPeriodStart('2025-03-01');
    awardsPage.fillPeriodEnd('2025-03-31');
    awardsPage.clickSave();

    cy.wait('@createAward');
    cy.get('.toast').should('contain.text', 'criado');
  });

  it('UC67: should edit an award batch', () => {
    awardsPage.visit();
    cy.wait('@getAwards');

    cy.intercept('PUT', '/api/v1/awards/batches/1', {
      statusCode: 200,
      body: { data: { id: 1, title: 'Premiados Janeiro Updated', status: 'published' } },
    }).as('updateAward');

    cy.intercept('GET', '/api/v1/awards/batches*', { fixture: 'awards/list.json' }).as('reloadAwards');

    awardsPage.clickEditOnRow(1);
    cy.get('#editModal').should('be.visible');

    cy.get('#f_title').clear().type('Premiados Janeiro Updated');
    awardsPage.clickSave();

    cy.wait('@updateAward');
    cy.get('.toast').should('contain.text', 'atualizado');
  });

  it('UC68: should manage results', () => {
    awardsPage.visit();
    cy.wait('@getAwards');

    cy.intercept('GET', '/api/v1/awards/batches/1', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          title: 'Premiados Janeiro',
          results: [{ game_id: 'vs20fruitsw', amount: 5000, position: 1 }],
        },
      },
    }).as('getAwardResults');

    cy.intercept('PUT', '/api/v1/awards/batches/1/results', {
      statusCode: 200,
      body: { message: 'Resultados sincronizados.' },
    }).as('syncResults');

    awardsPage.clickResultsOnRow(1);
    cy.get('.modal').should('be.visible');
  });

  it('UC69: should publish and archive a batch', () => {
    awardsPage.visit();
    cy.wait('@getAwards');

    // Publish
    cy.intercept('POST', '/api/v1/awards/batches/2/publish', {
      statusCode: 200,
      body: { data: { id: 2, status: 'published' } },
    }).as('publishAward');

    cy.intercept('GET', '/api/v1/awards/batches*', { fixture: 'awards/list.json' }).as('reloadAwards');

    awardsPage.clickPublishOnRow(2);
    cy.wait('@publishAward');
    cy.get('.toast').should('be.visible');

    // Archive
    cy.intercept('POST', '/api/v1/awards/batches/1/archive', {
      statusCode: 200,
      body: { data: { id: 1, status: 'archived' } },
    }).as('archiveAward');

    awardsPage.clickArchiveOnRow(1);
    cy.wait('@archiveAward');
    cy.get('.toast').should('be.visible');
  });
});
