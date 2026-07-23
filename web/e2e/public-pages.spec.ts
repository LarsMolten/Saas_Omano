import { test, expect } from "@playwright/test";

test.describe("Homepage", () => {
  test("renders homepage with hero section", async ({ page }) => {
    await page.goto("/");

    // Hero section
    await expect(
      page.getByRole("heading", {
        name: "Trouvez le prestataire ideal pour votre evenement",
      })
    ).toBeVisible();

    // CTA buttons
    await expect(
      page.getByRole("link", { name: "Rechercher un prestataire" })
    ).toBeVisible();
    await expect(
      page.getByRole("link", { name: "Devenir prestataire" })
    ).toBeVisible();
  });

  test("homepage links navigate correctly", async ({ page }) => {
    await page.goto("/");

    await page.getByRole("link", { name: "Rechercher un prestataire" }).click();
    await expect(page).toHaveURL(/\/recherche/);
  });

  test("renders footer CTA section", async ({ page }) => {
    await page.goto("/");
    await expect(
      page.getByRole("heading", { name: "Vous etes prestataire ?" })
    ).toBeVisible();
  });
});

test.describe("Search page", () => {
  test("renders search page with filters", async ({ page }) => {
    await page.goto("/recherche");

    await expect(
      page.getByRole("heading", { name: "Recherche de prestataires" })
    ).toBeVisible();

    // Filter form
    await expect(
      page.getByPlaceholder("Rechercher par nom, description...")
    ).toBeVisible();
    await expect(
      page.getByRole("button", { name: "Rechercher" })
    ).toBeVisible();
  });

  test("search returns results or empty state", async ({ page }) => {
    await page.goto("/recherche");

    // Either results or empty state message should be visible
    const resultsOrEmpty = page.locator(
      ".grid, text=Aucun prestataire ne correspond"
    );
    await expect(resultsOrEmpty.first()).toBeVisible({ timeout: 10000 });
  });

  test("search filters work", async ({ page }) => {
    await page.goto("/recherche");

    // Fill search
    await page
      .getByPlaceholder("Rechercher par nom, description...")
      .fill("test");
    await page.getByRole("button", { name: "Rechercher" }).click();

    // Should still show results or empty state
    const content = page.locator(
      ".grid, text=Aucun prestataire ne correspond"
    );
    await expect(content.first()).toBeVisible({ timeout: 10000 });
  });

  test("provider cards link to profile pages", async ({ page }) => {
    await page.goto("/recherche");

    // Wait for results
    await page.waitForSelector(".grid a[href^='/prestataire/']", {
      timeout: 10_000,
    }).catch(() => {
      // No providers in DB, skip this assertion
    });

    const links = page.locator("a[href^='/prestataire/']");
    const count = await links.count();
    if (count > 0) {
      await expect(links.first()).toHaveAttribute(
        "href",
        /\/prestataire\/.+/
      );
    }
  });
});

test.describe("Provider profile page", () => {
  test("navigates to provider profile from search", async ({ page }) => {
    await page.goto("/recherche");

    // Wait for provider cards
    const providerLink = page.locator("a[href^='/prestataire/']").first();
    const hasProviders = await providerLink
      .isVisible({ timeout: 5_000 })
      .catch(() => false);

    if (hasProviders) {
      const href = await providerLink.getAttribute("href");
      await providerLink.click();
      await expect(page).toHaveURL(href!);

      // Profile page should have provider name in heading
      await expect(page.locator("h1")).toBeVisible();
    }
  });

  test("profile page shows services tab", async ({ page }) => {
    await page.goto("/recherche");

    const providerLink = page.locator("a[href^='/prestataire/']").first();
    const hasProviders = await providerLink
      .isVisible({ timeout: 5_000 })
      .catch(() => false);

    if (hasProviders) {
      await providerLink.click();

      // Should have Services section or empty state
      const servicesOrEmpty = page.locator(
        "text=Services, text=Aucun service"
      );
      await expect(servicesOrEmpty.first()).toBeVisible({ timeout: 5_000 });
    }
  });

  test("profile page has SEO meta tags", async ({ page }) => {
    await page.goto("/recherche");

    const providerLink = page.locator("a[href^='/prestataire/']").first();
    const hasProviders = await providerLink
      .isVisible({ timeout: 5_000 })
      .catch(() => false);

    if (hasProviders) {
      await providerLink.click();
      await page.waitForURL(/\/prestataire\//);

      // Title should include provider name
      const title = await page.title();
      expect(title).toContain("Omano");
    }
  });

  test("profile page has Schema.org markup", async ({ page }) => {
    await page.goto("/recherche");

    const providerLink = page.locator("a[href^='/prestataire/']").first();
    const hasProviders = await providerLink
      .isVisible({ timeout: 5_000 })
      .catch(() => false);

    if (hasProviders) {
      await providerLink.click();
      await page.waitForURL(/\/prestataire\//);

      // Check for JSON-LD schema
      const schema = page.locator('script[type="application/ld+json"]');
      await expect(schema).toBeVisible({ timeout: 5_000 });

      const content = await schema.textContent();
      expect(content).toContain("LocalBusiness");
    }
  });

  test("profile page has CTA devis button", async ({ page }) => {
    await page.goto("/recherche");

    const providerLink = page.locator("a[href^='/prestataire/']").first();
    const hasProviders = await providerLink
      .isVisible({ timeout: 5_000 })
      .catch(() => false);

    if (hasProviders) {
      await providerLink.click();
      await page.waitForURL(/\/prestataire\//);

      await expect(
        page.getByRole("link", { name: "Demander un devis" }).first()
      ).toBeVisible();
    }
  });
});

test.describe("Category page", () => {
  test("renders category page from homepage link", async ({ page }) => {
    await page.goto("/");

    // Check if categories section exists
    const categoryLink = page.locator("a[href^='/categories/']").first();
    const hasCategories = await categoryLink
      .isVisible({ timeout: 5_000 })
      .catch(() => false);

    if (hasCategories) {
      await categoryLink.click();
      await page.waitForURL(/\/categories\//);

      // Should have a heading
      await expect(page.locator("h1")).toBeVisible();
    }
  });

  test("category page shows providers or empty state", async ({ page }) => {
    // Try a known category slug
    await page.goto("/categories/traiteur");

    // Either providers or empty state
    const content = page.locator(
      ".grid, text=Aucun prestataire dans cette categorie"
    );
    await expect(content.first()).toBeVisible({ timeout: 10_000 });
  });

  test("category page provider cards link to profiles", async ({ page }) => {
    await page.goto("/categories/traiteur");

    const providerLink = page.locator("a[href^='/prestataire/']").first();
    const hasProviders = await providerLink
      .isVisible({ timeout: 5_000 })
      .catch(() => false);

    if (hasProviders) {
      await providerLink.click();
      await page.waitForURL(/\/prestataire\//);
      await expect(page.locator("h1")).toBeVisible();
    }
  });
});

test.describe("Full flow: Search -> Profile -> Devis", () => {
  test("complete user journey from search to provider profile", async ({
    page,
  }) => {
    // Start at search
    await page.goto("/recherche");
    await expect(
      page.getByRole("heading", { name: "Recherche de prestataires" })
    ).toBeVisible();

    // Wait for any provider cards
    const providerLink = page.locator("a[href^='/prestataire/']").first();
    const hasProviders = await providerLink
      .isVisible({ timeout: 5_000 })
      .catch(() => false);

    if (!hasProviders) {
      test.skip();
      return;
    }

    // Click first provider
    const href = await providerLink.getAttribute("href");
    await providerLink.click();
    await expect(page).toHaveURL(href!);

    // Profile page loads
    await expect(page.locator("h1")).toBeVisible();
    const title = await page.title();
    expect(title).toContain("Omano");

    // Schema.org present
    const schema = page.locator('script[type="application/ld+json"]');
    await expect(schema).toBeVisible();

    // CTA devis present
    await expect(
      page.getByRole("link", { name: "Demander un devis" }).first()
    ).toBeVisible();
  });
});

test.describe("Navigation", () => {
  test("header navigation works", async ({ page }) => {
    await page.goto("/");

    // Header should be visible
    await expect(
      page.getByRole("link", { name: "Omano" }).first()
    ).toBeVisible();

    // Navigate to search
    await page.getByRole("link", { name: "Recherche" }).first().click();
    await expect(page).toHaveURL(/\/recherche/);

    // Navigate back to home
    await page.getByRole("link", { name: "Omano" }).first().click();
    await expect(page).toHaveURL("/");
  });

  test("404 for non-existent provider slug", async ({ page }) => {
    const response = await page.goto(
      "/prestataire/this-provider-does-not-exist-12345"
    );
    expect(response?.status()).toBe(404);
  });

  test("404 for non-existent category slug", async ({ page }) => {
    const response = await page.goto(
      "/categories/this-category-does-not-exist-12345"
    );
    expect(response?.status()).toBe(404);
  });
});
