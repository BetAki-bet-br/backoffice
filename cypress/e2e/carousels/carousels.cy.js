import CarouselsPage from '../../support/page-objects/CarouselsPage';

describe('Carousels', () => {
  const carouselsPage = new CarouselsPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/carousels*', { fixture: 'carousels/list.json' }).as('getCarousels');
  });

  it('UC18: should list carousels', () => {
    carouselsPage.visit();
    cy.wait('@getCarousels');

    cy.get('#tbody tr').should('have.length', 2);
    cy.get('#tbody').should('contain.text', 'Home Banner');
    cy.get('#tbody').should('contain.text', 'Promoções');
  });

  it('UC19: should search by name/slug', () => {
    carouselsPage.visit();
    cy.wait('@getCarousels');

    cy.intercept('GET', '/api/v1/carousels*q=Home*', {
      body: {
        data: [{ id: 1, name: 'Home Banner', slug: 'home-banner', slides_count: 3 }],
        current_page: 1,
        last_page: 1,
        total: 1,
      },
    }).as('searchCarousels');

    carouselsPage.searchByQuery('Home');
    cy.wait('@searchCarousels');

    cy.get('#tbody tr').should('have.length', 1);
    cy.get('#tbody').should('contain.text', 'Home Banner');
  });

  it('UC20: should create a carousel with slides', () => {
    carouselsPage.visit();
    cy.wait('@getCarousels');

    cy.intercept('POST', '/api/v1/carousels', {
      statusCode: 201,
      body: { data: { id: 3, name: 'New Carousel', slug: 'new-carousel' } },
    }).as('createCarousel');

    cy.intercept('GET', '/api/v1/carousels*', { fixture: 'carousels/list.json' }).as('reloadCarousels');

    carouselsPage.clickNew();

    cy.get('#editModal').should('be.visible');
    cy.get('#editTitle').should('contain.text', 'Novo Carrossel');

    carouselsPage.fillName('New Carousel');
    carouselsPage.fillSlug('new-carousel');

    // Add a slide
    carouselsPage.clickAddSlide();
    cy.get('.slide-form').should('have.length', 1);

    carouselsPage.fillSlideField(0, 'href', 'https://example.com/slide');
    carouselsPage.fillSlideField(0, 'alt', 'Test Slide');

    carouselsPage.clickSave();

    cy.wait('@createCarousel');
    cy.get('.toast').should('contain.text', 'Carrossel criado');
  });

  it('UC21: should edit a carousel', () => {
    carouselsPage.visit();
    cy.wait('@getCarousels');

    cy.intercept('GET', '/api/v1/carousels/home-banner*', {
      statusCode: 200,
      fixture: 'carousels/single.json',
    }).as('getCarousel');

    cy.intercept('POST', '/api/v1/carousels/1', {
      statusCode: 200,
      body: { data: { id: 1, name: 'Home Banner Updated', slug: 'home-banner' } },
    }).as('updateCarousel');

    cy.intercept('GET', '/api/v1/carousels*', { fixture: 'carousels/list.json' }).as('reloadCarousels');

    carouselsPage.clickEditOnRow(1);

    cy.wait('@getCarousel');
    cy.get('#editModal').should('be.visible');

    cy.get('#f_name').clear().type('Home Banner Updated');
    carouselsPage.clickSave();

    cy.wait('@updateCarousel');
    cy.get('.toast').should('contain.text', 'Carrossel atualizado');
  });

  it('UC22: should add and remove slides', () => {
    carouselsPage.visit();
    cy.wait('@getCarousels');

    carouselsPage.clickNew();
    cy.get('#editModal').should('be.visible');

    // Add slides
    carouselsPage.clickAddSlide();
    carouselsPage.clickAddSlide();
    cy.get('.slide-form').should('have.length', 2);

    // Remove first slide
    carouselsPage.removeSlide(0);
    cy.get('.slide-form').should('have.length', 1);
  });

  it('UC23: should delete a carousel', () => {
    carouselsPage.visit();
    cy.wait('@getCarousels');

    cy.intercept('DELETE', '/api/v1/carousels/2', {
      statusCode: 200,
      body: {},
    }).as('deleteCarousel');

    cy.intercept('GET', '/api/v1/carousels*', { fixture: 'carousels/list.json' }).as('reloadCarousels');

    cy.on('window:confirm', () => true);

    carouselsPage.clickDeleteOnRow(2);

    cy.wait('@deleteCarousel');
    cy.get('.toast').should('contain.text', 'Carrossel excluído');
  });
});
