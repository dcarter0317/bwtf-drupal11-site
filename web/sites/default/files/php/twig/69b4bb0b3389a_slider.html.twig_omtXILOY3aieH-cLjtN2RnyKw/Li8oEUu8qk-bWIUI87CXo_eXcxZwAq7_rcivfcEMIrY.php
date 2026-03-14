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

/* @bwtf/templates/components/slider.html.twig */
class __TwigTemplate_728ab9d56ba00015b268241799aaf239 extends Template
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
        // line 2
        $context["slides"] = ((array_key_exists("bwtf_slides", $context)) ? (Twig\Extension\CoreExtension::default(($context["bwtf_slides"] ?? null), [])) : ([]));
        // line 3
        $context["show_slider"] = ((array_key_exists("slider_display", $context)) ? (Twig\Extension\CoreExtension::default(($context["slider_display"] ?? null), 0)) : (0));
        // line 4
        $context["on_front"] = ((array_key_exists("is_front", $context)) ? (($context["is_front"] ?? null)) : (true));
        // line 5
        $context["aspect"] = ((array_key_exists("slider_aspect", $context)) ? (Twig\Extension\CoreExtension::default(($context["slider_aspect"] ?? null), "16:9")) : ("16:9"));
        // line 6
        $context["ratio_class"] = (((($context["aspect"] ?? null) == "21:9")) ? ("ratio-21x9") : ("ratio-16x9"));
        // line 7
        yield "
";
        // line 9
        $context["overlay_class"] = ((array_key_exists("slider_overlay_preset", $context)) ? (Twig\Extension\CoreExtension::default(($context["slider_overlay_preset"] ?? null), "overlay-solid")) : ("overlay-solid"));
        // line 10
        $context["overlay_parts"] = [];
        // line 11
        if ((array_key_exists("slider_overlay_from", $context) && ($context["slider_overlay_from"] ?? null))) {
            $context["_"] = CoreExtension::getAttribute($this->env, $this->source, ($context["overlay_parts"] ?? null), "append", [("--hero-overlay: " . ($context["slider_overlay_from"] ?? null))], "method", false, false, true, 11);
        }
        // line 12
        if ((array_key_exists("slider_overlay_to", $context) && ($context["slider_overlay_to"] ?? null))) {
            $context["_"] = CoreExtension::getAttribute($this->env, $this->source, ($context["overlay_parts"] ?? null), "append", [("--hero-overlay-to: " . ($context["slider_overlay_to"] ?? null))], "method", false, false, true, 12);
        }
        // line 13
        $context["overlay_style"] = (((($tmp = Twig\Extension\CoreExtension::length($this->env->getCharset(), ($context["overlay_parts"] ?? null))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ((Twig\Extension\CoreExtension::join(($context["overlay_parts"] ?? null), "; ") . ";")) : (null));
        // line 14
        yield "
";
        // line 15
        if (((($context["on_front"] ?? null) && ($context["show_slider"] ?? null)) && (Twig\Extension\CoreExtension::length($this->env->getCharset(), ($context["slides"] ?? null)) > 0))) {
            // line 16
            yield "<section class=\"hero ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["ratio_class"] ?? null), "html", null, true);
            yield " ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["overlay_class"] ?? null), "html", null, true);
            yield "\"
         ";
            // line 17
            if ((($tmp = ($context["overlay_style"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "style=\"";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["overlay_style"] ?? null), "html_attr");
                yield "\"";
            }
            // line 18
            yield "         role=\"region\" aria-label=\"Homepage slider\">
  <div class=\"swiper mySwiper\">
    <div class=\"swiper-wrapper\">

      ";
            // line 22
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["slides"] ?? null));
            $context['loop'] = [
              'parent' => $context['_parent'],
              'index0' => 0,
              'index'  => 1,
              'first'  => true,
            ];
            if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                $length = count($context['_seq']);
                $context['loop']['revindex0'] = $length - 1;
                $context['loop']['revindex'] = $length;
                $context['loop']['length'] = $length;
                $context['loop']['last'] = 1 === $length;
            }
            foreach ($context['_seq'] as $context["_key"] => $context["s"]) {
                // line 23
                yield "        ";
                // line 24
                yield "        ";
                $context["choice"] = (((($context["aspect"] ?? null) == "21:9")) ? (((CoreExtension::getAttribute($this->env, $this->source, $context["s"], "img21", [], "any", true, true, true, 24)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, $context["s"], "img21", [], "any", false, false, true, 24), [])) : ([]))) : (((CoreExtension::getAttribute($this->env, $this->source, $context["s"], "img16", [], "any", true, true, true, 24)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, $context["s"], "img16", [], "any", false, false, true, 24), [])) : ([]))));
                // line 25
                yield "        ";
                $context["img_src"] = ((CoreExtension::getAttribute($this->env, $this->source, ($context["choice"] ?? null), "src", [], "any", true, true, true, 25)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, ($context["choice"] ?? null), "src", [], "any", false, false, true, 25), ((CoreExtension::getAttribute($this->env, $this->source, $context["s"], "image_url", [], "any", true, true, true, 25)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, $context["s"], "image_url", [], "any", false, false, true, 25), "")) : ("")))) : (((CoreExtension::getAttribute($this->env, $this->source, $context["s"], "image_url", [], "any", true, true, true, 25)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, $context["s"], "image_url", [], "any", false, false, true, 25), "")) : (""))));
                // line 26
                yield "
        <div class=\"swiper-slide\">
          <div class=\"slide-media\">
            <div class=\"slide-overlay\" aria-hidden=\"true\"></div>

            ";
                // line 31
                if ((($tmp = ($context["img_src"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 32
                    yield "              ";
                    if ((($tmp = ((CoreExtension::getAttribute($this->env, $this->source, ($context["choice"] ?? null), "is_svg", [], "any", true, true, true, 32)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, ($context["choice"] ?? null), "is_svg", [], "any", false, false, true, 32), false)) : (false))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 33
                        yield "                <img
                  src=\"";
                        // line 34
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["img_src"] ?? null), "html_attr");
                        yield "\"
                  alt=\"";
                        // line 35
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ((CoreExtension::getAttribute($this->env, $this->source, $context["s"], "title", [], "any", true, true, true, 35)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, $context["s"], "title", [], "any", false, false, true, 35), ("Slide " . CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, true, 35)))) : (("Slide " . CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, true, 35)))));
                        yield "\"
                  ";
                        // line 36
                        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "first", [], "any", false, false, true, 36)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                            yield "loading=\"eager\" decoding=\"sync\"";
                        } else {
                            yield "loading=\"lazy\" decoding=\"async\"";
                        }
                        yield ">
              ";
                    } else {
                        // line 38
                        yield "                <img
                  src=\"";
                        // line 39
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["img_src"] ?? null), "html_attr");
                        yield "\"
                  ";
                        // line 40
                        if ((($tmp = ((CoreExtension::getAttribute($this->env, $this->source, ($context["choice"] ?? null), "srcset", [], "any", true, true, true, 40)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, ($context["choice"] ?? null), "srcset", [], "any", false, false, true, 40), "")) : (""))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                            yield "srcset=\"";
                            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["choice"] ?? null), "srcset", [], "any", false, false, true, 40), "html_attr");
                            yield "\"";
                        }
                        // line 41
                        yield "                  sizes=\"100vw\"
                  alt=\"";
                        // line 42
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ((CoreExtension::getAttribute($this->env, $this->source, $context["s"], "title", [], "any", true, true, true, 42)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, $context["s"], "title", [], "any", false, false, true, 42), ("Slide " . CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, true, 42)))) : (("Slide " . CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, true, 42)))));
                        yield "\"
                  ";
                        // line 43
                        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "first", [], "any", false, false, true, 43)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                            yield "loading=\"eager\" decoding=\"sync\"";
                        } else {
                            yield "loading=\"lazy\" decoding=\"async\"";
                        }
                        yield ">
              ";
                    }
                    // line 45
                    yield "            ";
                }
                // line 46
                yield "          </div>

          <div class=\"slide-text\">
            ";
                // line 49
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["s"], "title", [], "any", false, false, true, 49)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    yield "<h3 class=\"slide-text-title\">";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["s"], "title", [], "any", false, false, true, 49), "html", null, true);
                    yield "</h3>";
                }
                // line 50
                yield "
            ";
                // line 51
                if ((CoreExtension::getAttribute($this->env, $this->source, $context["s"], "desc", [], "any", true, true, true, 51) && CoreExtension::getAttribute($this->env, $this->source, $context["s"], "desc", [], "any", false, false, true, 51))) {
                    // line 52
                    yield "              ";
                    if (is_iterable(CoreExtension::getAttribute($this->env, $this->source, $context["s"], "desc", [], "any", false, false, true, 52))) {
                        // line 53
                        yield "                <div class=\"slide-text-description\">";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(CoreExtension::getAttribute($this->env, $this->source, $context["s"], "desc", [], "any", false, false, true, 53)), "html", null, true);
                        yield "</div>
              ";
                    } else {
                        // line 55
                        yield "                <p class=\"slide-text-description\">";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["s"], "desc", [], "any", false, false, true, 55), "html", null, true);
                        yield "</p>
              ";
                    }
                    // line 57
                    yield "            ";
                }
                // line 58
                yield "
            ";
                // line 59
                if ((($tmp = ((CoreExtension::getAttribute($this->env, $this->source, $context["s"], "href", [], "any", true, true, true, 59)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, $context["s"], "href", [], "any", false, false, true, 59), "")) : (""))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 60
                    yield "              <a href=\"";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["s"], "href", [], "any", false, false, true, 60), "html_attr");
                    yield "\" class=\"btn btn--primary\">
                ";
                    // line 61
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ((CoreExtension::getAttribute($this->env, $this->source, $context["s"], "link_text", [], "any", true, true, true, 61)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, $context["s"], "link_text", [], "any", false, false, true, 61), "Learn more")) : ("Learn more")), "html", null, true);
                    yield "
              </a>
            ";
                }
                // line 64
                yield "          </div>
        </div>
      ";
                ++$context['loop']['index0'];
                ++$context['loop']['index'];
                $context['loop']['first'] = false;
                if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                    --$context['loop']['revindex0'];
                    --$context['loop']['revindex'];
                    $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['s'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 67
            yield "
    </div>

    ";
            // line 70
            if ((Twig\Extension\CoreExtension::length($this->env->getCharset(), ($context["slides"] ?? null)) > 1)) {
                // line 71
                yield "      <div class=\"swiper-button-next\" aria-label=\"Next slide\"></div>
      <div class=\"swiper-button-prev\" aria-label=\"Previous slide\"></div>
      <div class=\"swiper-pagination\"></div>
    ";
            }
            // line 75
            yield "  </div>

  <noscript>
    <div class=\"swiper-fallback\">
      ";
            // line 79
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["slides"] ?? null));
            $context['loop'] = [
              'parent' => $context['_parent'],
              'index0' => 0,
              'index'  => 1,
              'first'  => true,
            ];
            if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                $length = count($context['_seq']);
                $context['loop']['revindex0'] = $length - 1;
                $context['loop']['revindex'] = $length;
                $context['loop']['length'] = $length;
                $context['loop']['last'] = 1 === $length;
            }
            foreach ($context['_seq'] as $context["_key"] => $context["s"]) {
                // line 80
                yield "        ";
                if ((($tmp = ((CoreExtension::getAttribute($this->env, $this->source, $context["s"], "image_url", [], "any", true, true, true, 80)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, $context["s"], "image_url", [], "any", false, false, true, 80), "")) : (""))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 81
                    yield "          <img src=\"";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["s"], "image_url", [], "any", false, false, true, 81), "html_attr");
                    yield "\" alt=\"";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ((CoreExtension::getAttribute($this->env, $this->source, $context["s"], "title", [], "any", true, true, true, 81)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, $context["s"], "title", [], "any", false, false, true, 81), ("Slide " . CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, true, 81)))) : (("Slide " . CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, true, 81)))));
                    yield "\">
        ";
                }
                // line 83
                yield "      ";
                ++$context['loop']['index0'];
                ++$context['loop']['index'];
                $context['loop']['first'] = false;
                if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                    --$context['loop']['revindex0'];
                    --$context['loop']['revindex'];
                    $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['s'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 84
            yield "    </div>
  </noscript>
</section>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["bwtf_slides", "slider_display", "is_front", "slider_aspect", "slider_overlay_preset", "slider_overlay_from", "slider_overlay_to", "loop"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@bwtf/templates/components/slider.html.twig";
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
        return array (  311 => 84,  297 => 83,  289 => 81,  286 => 80,  269 => 79,  263 => 75,  257 => 71,  255 => 70,  250 => 67,  234 => 64,  228 => 61,  223 => 60,  221 => 59,  218 => 58,  215 => 57,  209 => 55,  203 => 53,  200 => 52,  198 => 51,  195 => 50,  189 => 49,  184 => 46,  181 => 45,  172 => 43,  168 => 42,  165 => 41,  159 => 40,  155 => 39,  152 => 38,  143 => 36,  139 => 35,  135 => 34,  132 => 33,  129 => 32,  127 => 31,  120 => 26,  117 => 25,  114 => 24,  112 => 23,  95 => 22,  89 => 18,  83 => 17,  76 => 16,  74 => 15,  71 => 14,  69 => 13,  65 => 12,  61 => 11,  59 => 10,  57 => 9,  54 => 7,  52 => 6,  50 => 5,  48 => 4,  46 => 3,  44 => 2,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@bwtf/templates/components/slider.html.twig", "/var/www/html/web/themes/custom/bwtf/templates/components/slider.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 2, "if" => 11, "for" => 22];
        static $filters = ["default" => 2, "length" => 13, "join" => 13, "escape" => 16, "e" => 17, "render" => 53];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'if', 'for'],
                ['default', 'length', 'join', 'escape', 'e', 'render'],
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
