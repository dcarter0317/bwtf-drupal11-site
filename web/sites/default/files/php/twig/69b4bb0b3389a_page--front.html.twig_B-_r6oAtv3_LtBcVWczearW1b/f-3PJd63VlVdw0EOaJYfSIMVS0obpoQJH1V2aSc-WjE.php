<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* themes/custom/bwtf/templates/page--front.html.twig */
class __TwigTemplate_ebe450ecb56577275bd806533fe67183 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 1
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_prefix"] ?? null), "html", null, true);
        yield "
";
        // line 2
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_suffix"] ?? null), "html", null, true);
        yield "

";
        // line 4
        yield from $this->load("@bwtf/templates/layout/header.html.twig", 4)->unwrap()->yield($context);
        // line 5
        yield "
<section class=\"nav-container\">
 ";
        // line 7
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "main_menu", [], "any", false, false, true, 7)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 8
            yield "   ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "main_menu", [], "any", false, false, true, 8), "html", null, true);
            yield " 
  ";
        }
        // line 10
        yield "</section>


 ";
        // line 13
        yield from $this->load("@bwtf/templates/components/slider.html.twig", 13)->unwrap()->yield($context);
        // line 14
        yield "
<div class=\"top-ad-section\">
    ";
        // line 16
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "simple_ads", [], "any", false, false, true, 16)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 17
            yield "     ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "simple_ads", [], "any", false, false, true, 17), "html", null, true);
            yield "
    ";
        }
        // line 19
        yield "</div>

 <section class=\"content\">
  <main>
    <section class=\"article-cards container\">
         ";
        // line 25
        yield "         ";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, views_embed_view("blogs", "latest_news"), "html", null, true);
        yield "
    </section>
  </main>
  
  ";
        // line 29
        yield from $this->load("@bwtf/templates/layout/sidebar.html.twig", 29)->unwrap()->yield($context);
        // line 30
        yield " </section>

";
        // line 32
        yield from $this->load("@bwtf/templates/layout/footer.html.twig", 32)->unwrap()->yield($context);
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["title_prefix", "title_suffix", "page"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/bwtf/templates/page--front.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  107 => 32,  103 => 30,  101 => 29,  93 => 25,  86 => 19,  80 => 17,  78 => 16,  74 => 14,  72 => 13,  67 => 10,  61 => 8,  59 => 7,  55 => 5,  53 => 4,  48 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/bwtf/templates/page--front.html.twig", "/var/www/html/web/themes/custom/bwtf/templates/page--front.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["include" => 4, "if" => 7];
        static $filters = ["escape" => 1];
        static $functions = ["drupal_view" => 25];

        try {
            $this->sandbox->checkSecurity(
                ['include', 'if'],
                ['escape'],
                ['drupal_view'],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
