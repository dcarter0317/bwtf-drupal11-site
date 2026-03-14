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

/* themes/custom/bwtf/templates/layout/menu--main.html.twig */
class __TwigTemplate_023c8ba80ad8dbb95cef423bf17e904a extends Template
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
        // line 22
        yield "
";
        // line 27
        yield "
<section class=\"menu-container\">
  <div class=\"menu-btn\">
    <div class=\"bar bar-1\"></div>
    <div class=\"bar bar-2\"></div>
    <div class=\"bar bar-3\"></div>
  </div>
</section>

<section class=\"nav-container\">
  ";
        // line 37
        if ((($tmp = ($context["items"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 38
            yield "    <ul class=\"nav\">
      ";
            // line 39
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["items"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["item"]) {
                // line 40
                yield "        ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["item"], "title", [], "any", false, false, true, 40)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 41
                    yield "          <li";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "attributes", [], "any", false, false, true, 41), "html", null, true);
                    yield ">
            ";
                    // line 42
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->getLink(CoreExtension::getAttribute($this->env, $this->source, $context["item"], "title", [], "any", false, false, true, 42), CoreExtension::getAttribute($this->env, $this->source, $context["item"], "url", [], "any", false, false, true, 42)), "html", null, true);
                    yield "
            ";
                    // line 43
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["item"], "below", [], "any", false, false, true, 43)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 44
                        yield "              <ul>
                ";
                        // line 45
                        $context['_parent'] = $context;
                        $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["item"], "below", [], "any", false, false, true, 45));
                        foreach ($context['_seq'] as $context["_key"] => $context["child"]) {
                            // line 46
                            yield "                  ";
                            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["child"], "title", [], "any", false, false, true, 46)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                                // line 47
                                yield "                    <li";
                                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["child"], "attributes", [], "any", false, false, true, 47), "html", null, true);
                                yield ">
                      ";
                                // line 48
                                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->getLink(CoreExtension::getAttribute($this->env, $this->source, $context["child"], "title", [], "any", false, false, true, 48), CoreExtension::getAttribute($this->env, $this->source, $context["child"], "url", [], "any", false, false, true, 48)), "html", null, true);
                                yield "
                    </li>
                  ";
                            }
                            // line 51
                            yield "                ";
                        }
                        $_parent = $context['_parent'];
                        unset($context['_seq'], $context['_key'], $context['child'], $context['_parent']);
                        $context = array_intersect_key($context, $_parent) + $_parent;
                        // line 52
                        yield "              </ul>
            ";
                    }
                    // line 54
                    yield "          </li>
        ";
                }
                // line 56
                yield "      ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['item'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 57
            yield "    </ul>
  ";
        }
        // line 59
        yield "</section>";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["items"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/bwtf/templates/layout/menu--main.html.twig";
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
        return array (  127 => 59,  123 => 57,  117 => 56,  113 => 54,  109 => 52,  103 => 51,  97 => 48,  92 => 47,  89 => 46,  85 => 45,  82 => 44,  80 => 43,  76 => 42,  71 => 41,  68 => 40,  64 => 39,  61 => 38,  59 => 37,  47 => 27,  44 => 22,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/bwtf/templates/layout/menu--main.html.twig", "/var/www/html/web/themes/custom/bwtf/templates/layout/menu--main.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 37, "for" => 39];
        static $filters = ["escape" => 41];
        static $functions = ["link" => 42];

        try {
            $this->sandbox->checkSecurity(
                ['if', 'for'],
                ['escape'],
                ['link'],
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
