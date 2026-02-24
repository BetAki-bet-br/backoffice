import BannersPage from '../../support/page-objects/BannersPage';

describe('Banners', () => {
  const bannersPage = new BannersPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/banners*', { fixture: 'banners/list.json' }).as('getBanners');
  });

  it('UC10: should list banners in a paginated table', () => {
    bannersPage.visit();
    cy.wait('@getBanners');

    cy.get('#tbody tr').should('have.length', 3);
    cy.get('#tbody').should('contain.text', 'promo-verao');
    cy.get('#tbody').should('contain.text', 'bonus-boas-vindas');
    cy.get('#tbody').should('contain.text', 'live-casino-vip');

    bannersPage.getPaginationInfo().should('contain.text', 'Carregados: 3');
  });

  it('UC11: should filter banners by status', () => {
    bannersPage.visit();
    cy.wait('@getBanners');

    cy.intercept('GET', '/api/v1/banners*status=draft*', {
      body: {
        data: [{ id: 2, slug: 'bonus-boas-vindas', vertical: 'sportbook', countries: ['BR'], status: 'draft', publish_at: null, expire_at: null }],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('getBannersDraft');

    bannersPage.filterByStatus('draft');
    cy.wait('@getBannersDraft');

    cy.get('#tbody tr').should('have.length', 1);
    cy.get('#tbody').should('contain.text', 'bonus-boas-vindas');
  });

  it('UC12: should filter banners by vertical', () => {
    bannersPage.visit();
    cy.wait('@getBanners');

    cy.intercept('GET', '/api/v1/banners*vertical=casino*', {
      body: {
        data: [{ id: 1, slug: 'promo-verao', vertical: 'casino', countries: ['BR', 'PT'], status: 'published', publish_at: '2025-01-01T00:00:00Z', expire_at: '2025-12-31T23:59:59Z' }],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('getBannersCasino');

    bannersPage.filterByVertical('casino');
    cy.wait('@getBannersCasino');

    cy.get('#tbody tr').should('have.length', 1);
    cy.get('#tbody').should('contain.text', 'promo-verao');
  });

  it('UC13: should search banners by slug', () => {
    bannersPage.visit();
    cy.wait('@getBanners');

    cy.intercept('GET', '/api/v1/banners*q=promo*', {
      body: {
        data: [{ id: 1, slug: 'promo-verao', vertical: 'casino', countries: ['BR', 'PT'], status: 'published', publish_at: '2025-01-01T00:00:00Z', expire_at: '2025-12-31T23:59:59Z' }],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('searchBanners');

    bannersPage.searchBySlug('promo');
    cy.wait('@searchBanners');

    cy.get('#tbody tr').should('have.length', 1);
    cy.get('#tbody').should('contain.text', 'promo-verao');
  });

  it('UC14: should create a new banner via modal', () => {
    bannersPage.visit();
    cy.wait('@getBanners');

    cy.intercept('POST', '/api/v1/banners', {
      statusCode: 201,
      body: { data: { id: 4, slug: 'new-banner', vertical: 'casino', status: 'draft' } },
    }).as('createBanner');

    cy.intercept('GET', '/api/v1/banners*', { fixture: 'banners/list.json' }).as('reloadBanners');

    bannersPage.clickNew();

    cy.get('#editModal').should('be.visible');
    cy.get('#editTitle').should('contain.text', 'Novo Banner');

    bannersPage.fillSlug('new-banner');
    bannersPage.selectVertical('casino');
    bannersPage.selectStatus('draft');
    bannersPage.fillCountries('BR,PT');
    bannersPage.clickSave();

    cy.wait('@createBanner').its('request.body').should('deep.include', {
      slug: 'new-banner',
      vertical: 'casino',
      status: 'draft',
    });

    cy.get('.toast').should('contain.text', 'Banner criado');
  });

  it('UC15: should edit an existing banner', () => {
    bannersPage.visit();
    cy.wait('@getBanners');

    cy.intercept('PUT', '/api/v1/banners/1', {
      statusCode: 200,
      body: { data: { id: 1, slug: 'promo-verao-updated', vertical: 'casino', status: 'draft' } },
    }).as('updateBanner');

    cy.intercept('GET', '/api/v1/banners*', { fixture: 'banners/list.json' }).as('reloadBanners');

    bannersPage.clickEditOnRow(1);

    cy.get('#editModal').should('be.visible');
    cy.get('#editTitle').should('contain.text', 'Editar Banner #1');

    // Verify form is pre-filled
    cy.get('#f_slug').should('have.value', 'promo-verao');
    cy.get('#f_vertical').should('have.value', 'casino');

    cy.get('#f_slug').clear().type('promo-verao-updated');
    bannersPage.clickSave();

    cy.wait('@updateBanner');
    cy.get('.toast').should('contain.text', 'Banner atualizado');
  });

  it('UC16: should delete a banner', () => {
    bannersPage.visit();
    cy.wait('@getBanners');

    cy.intercept('DELETE', '/api/v1/banners/3', {
      statusCode: 200,
      body: {},
    }).as('deleteBanner');

    cy.intercept('GET', '/api/v1/banners*', { fixture: 'banners/list.json' }).as('reloadBanners');

    cy.on('window:confirm', () => true);

    bannersPage.clickDeleteOnRow(3);

    cy.wait('@deleteBanner');
    cy.get('.toast').should('contain.text', 'Banner excluído');
  });

  it('UC17: should publish a banner', () => {
    bannersPage.visit();
    cy.wait('@getBanners');

    cy.intercept('POST', '/api/v1/banners/2/publish', {
      statusCode: 200,
      body: { data: { id: 2, status: 'published' } },
    }).as('publishBanner');

    cy.intercept('GET', '/api/v1/banners*', { fixture: 'banners/list.json' }).as('reloadBanners');

    bannersPage.clickPublishOnRow(2);

    cy.wait('@publishBanner');
    cy.get('.toast').should('contain.text', 'Banner publicado');
  });
});
