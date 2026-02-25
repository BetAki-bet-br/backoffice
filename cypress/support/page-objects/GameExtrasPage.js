import BasePage from './BasePage';

export default class GameExtrasPage extends BasePage {
  constructor() {
    super('/game-extras');
  }

  getSearchInput() {
    return cy.get('#q');
  }

  searchByQuery(query) {
    this.getSearchInput().clear().type(query);
    cy.get('#btnSearch').click();
    return this;
  }

  clearFilters() {
    cy.get('#btnClear').click();
    return this;
  }

  clickSync() {
    cy.get('#btnSync').click();
    return this;
  }

  clickReload() {
    cy.get('#btnReload').click();
    return this;
  }

  // --- Pagination ---

  clickNext() {
    cy.get('#nextBtn, .page-link').contains(/Próxim|Next|›/).click();
    return this;
  }

  clickPrev() {
    cy.get('#prevBtn, .page-link').contains(/Anterior|Prev|‹/).click();
    return this;
  }

  getPaginationInfo() {
    return cy.get('#paginationInfo');
  }
}
