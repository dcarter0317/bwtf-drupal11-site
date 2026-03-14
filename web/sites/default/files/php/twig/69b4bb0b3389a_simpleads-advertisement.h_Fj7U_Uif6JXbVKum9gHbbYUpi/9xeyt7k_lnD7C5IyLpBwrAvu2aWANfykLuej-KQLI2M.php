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

/* modules/contrib/simpleads/templates/simpleads-advertisement.html.twig */
class __TwigTemplate_8da4be006473867f220ea0916dcd51f6 extends Template
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
        // line 7
        yield "<div class=\"simpleads\" data-group=\"";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["group"] ?? null), "html", null, true);
        yield "\" data-ref-node=\"";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["node_ref_field"] ?? null), "html", null, true);
        yield "\" data-ref-simpleads=\"";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["simpleads_ref_field"] ?? null), "html", null, true);
        yield "\" data-rotation-type=\"";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["rotation_type"] ?? null), "html", null, true);
        yield "\" data-random-limit=\"";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["multiple_random_limit"] ?? null), "html", null, true);
        yield "\" data-impressions=\"";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["impressions"] ?? null), "html", null, true);
        yield "\"";
        if ((($tmp = (($_v0 = ($context["rotation_options"] ?? null)) && is_array($_v0) || $_v0 instanceof ArrayAccess && in_array($_v0::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v0[($context["rotation_type"] ?? null)] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["rotation_options"] ?? null), ($context["rotation_type"] ?? null), [], "array", false, false, true, 7))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield " data-rotation-options=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, (($_v1 = ($context["rotation_options"] ?? null)) && is_array($_v1) || $_v1 instanceof ArrayAccess && in_array($_v1::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v1[($context["rotation_type"] ?? null)] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["rotation_options"] ?? null), ($context["rotation_type"] ?? null), [], "array", false, false, true, 7)), "html", null, true);
            yield "\"";
        }
        if ((($tmp = ($context["show_in_modal"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield " data-is-modal=\"true\" data-modal-options=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["modal_options"] ?? null), "html", null, true);
            yield "\"";
        }
        yield "></div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["group", "node_ref_field", "simpleads_ref_field", "rotation_type", "multiple_random_limit", "impressions", "rotation_options", "show_in_modal", "modal_options"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "modules/contrib/simpleads/templates/simpleads-advertisement.html.twig";
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
        return array (  44 => 7,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "modules/contrib/simpleads/templates/simpleads-advertisement.html.twig", "/var/www/html/web/modules/contrib/simpleads/templates/simpleads-advertisement.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 7];
        static $filters = ["escape" => 7];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape'],
                [],
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
